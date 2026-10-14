<?php

namespace Modules\Admission\Services\Payment;

use App\Support\Http\Exceptions\ConflictException;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Modules\Admission\Enums\InvoiceType;
use Modules\Admission\Enums\Lifecycle;
use Modules\Admission\Enums\PmbInvoiceStatus;
use Modules\Admission\Enums\RegistrationPaymentStatus;
use Modules\Admission\Enums\TransactionStatus;
use Modules\Admission\Exceptions\DomainRuleException;
use Modules\Admission\Models\AdmissionDecision;
use Modules\Admission\Models\Application;
use Modules\Admission\Models\InvoiceItem;
use Modules\Admission\Models\PaymentTransaction;
use Modules\Admission\Models\PmbInvoice;
use Modules\Admission\Models\Voucher;
use Modules\Admission\Outbox\OutboxRecorder;
use Modules\Admission\Services\Configuration\PeriodService;
use Modules\Admission\Support\Actor;
use Modules\Admission\Support\HistoryRecorder;
use Modules\Admission\Support\Money;
use Modules\Admission\Support\PdfRenderer;
use Modules\Admission\Support\PmbSettings;
use Modules\Admission\Support\SequenceGenerator;
use Modules\Admission\Support\StateTransition;
use Modules\FileManagement\Models\FileUpload;

/**
 * Mesin tagihan PMB (05 §F.1–F.3, SM-04): tagihan pendaftaran (lazy) &
 * biaya pendidikan awal (07 K.2 tahap 7) dengan cicilan & potongan.
 * Nominal disalin saat dibuat — perubahan tarif tidak memengaruhi tagihan terbit.
 */
class InvoiceService
{
    public function __construct(
        private readonly SequenceGenerator $sequences,
        private readonly HistoryRecorder $history,
        private readonly OutboxRecorder $outbox,
        private readonly PeriodService $periods,
        private readonly PmbSettings $settings,
        private readonly PdfRenderer $pdf,
    ) {}

    /**
     * FR-PAY-01: dibuat saat pendaftar membuka langkah Pembayaran; biaya 0 →
     * tidak dibuat (BR-14). Satu tagihan aktif per aplikasi & jenis.
     */
    public function ensureRegistrationInvoice(Application $application): ?PmbInvoice
    {
        $fee = (string) ($application->config_snapshot['registration_fee'] ?? '0');

        if (Money::isZero($fee)) {
            return null;
        }

        $existing = PmbInvoice::query()->where('application_id', $application->getKey())->where('type', InvoiceType::RegistrationFee->value)
            ->whereNotIn('status', [PmbInvoiceStatus::Cancelled->value, PmbInvoiceStatus::Refunded->value])->first();

        if ($existing !== null) {
            return $existing;
        }

        return DB::transaction(function () use ($application, $fee): PmbInvoice {
            Application::query()->whereKey($application->getKey())->lockForUpdate()->first();
            $existing = PmbInvoice::query()->where('application_id', $application->getKey())->where('type', InvoiceType::RegistrationFee->value)
                ->whereNotIn('status', [PmbInvoiceStatus::Cancelled->value, PmbInvoiceStatus::Refunded->value])->first();

            if ($existing !== null) {
                return $existing;
            }

            $invoice = $this->createInvoice($application, InvoiceType::RegistrationFee, CarbonImmutable::parse($application->config_snapshot['payment_deadline_at']), [[
                'item_type' => 'charge', 'source_type' => 'registration_fee', 'source_id' => $application->wave_path_id,
                'code' => 'REG', 'description' => 'Biaya pendaftaran '.($application->config_snapshot['path_code'] ?? ''), 'amount' => $fee,
            ]], null);

            $this->syncRegistrationStatus($application);

            return $invoice;
        });
    }

    /**
     * Tagihan biaya pendidikan awal dari komponen tarif (prodi, sistem, jalur):
     * cicilan per rencana; potongan KIP 100% bila status KIP terverifikasi
     * (FR-SPC-02) atau beasiswa jalur masuk (`scholarship_award`, FR-SPC-06).
     */
    public function createInitialTuitionInvoice(Application $application, AdmissionDecision $decision, CarbonImmutable $dueAt): PmbInvoice
    {
        $existing = PmbInvoice::query()->where('application_id', $application->getKey())->where('type', InvoiceType::InitialTuition->value)
            ->whereNotIn('status', [PmbInvoiceStatus::Cancelled->value, PmbInvoiceStatus::Refunded->value])->first();

        if ($existing !== null) {
            return $existing;
        }

        $components = $this->periods->effectiveFeeComponents((string) $application->period_id, (string) $decision->study_program_id, (string) $decision->study_system_id, (string) $application->admission_path_id);
        $items = [];
        $minimumCents = 0;
        $fullRequired = $this->settings->get('pmb.enrollment_min_payment') === 'full';

        foreach ($components as $component) {
            $amount = (string) $component->amount;

            if (! $component->installable || empty($component->installment_plan)) {
                $items[] = ['item_type' => 'charge', 'source_type' => 'fee_component', 'source_id' => $component->getKey(), 'code' => $component->component_code, 'description' => $component->name, 'amount' => $amount, 'installment_seq' => null, 'due_at' => $dueAt];

                if ($component->is_mandatory_for_enrollment) {
                    $minimumCents += Money::toCents($amount);
                }

                continue;
            }

            $allocated = 0;
            $plan = array_values($component->installment_plan);

            foreach ($plan as $index => $term) {
                $isLast = $index === count($plan) - 1;
                $share = $isLast ? Money::fromCents(Money::toCents($amount) - $allocated) : Money::percent($amount, (float) $term['percent']);
                $allocated += Money::toCents($share);
                $termDue = $index === 0 ? $dueAt : $dueAt->addDays((int) ($term['due_days'] ?? 30 * $index));
                $items[] = ['item_type' => 'charge', 'source_type' => 'fee_component', 'source_id' => $component->getKey(), 'code' => $component->component_code, 'description' => $component->name.' (cicilan '.($index + 1).')', 'amount' => $share, 'installment_seq' => $index + 1, 'due_at' => $termDue];

                if ($component->is_mandatory_for_enrollment && ($index === 0 || $fullRequired)) {
                    $minimumCents += Money::toCents($share);
                }
            }
        }

        if ($items === []) {
            throw new ConflictException('Tarif biaya pendidikan awal untuk prodi/sistem kuliah ini belum diatur.', 'FEES_NOT_CONFIGURED');
        }

        $grossCents = array_sum(array_map(fn ($i) => Money::toCents($i['amount']), $items));
        $discountPercent = $this->discountPercent($application, $decision);

        if ($discountPercent > 0) {
            $source = $application->kip_status === 'verified' || $application->kip_status === 'accepted_by_program' ? 'kip' : 'scholarship';
            $items[] = ['item_type' => 'discount', 'source_type' => $source, 'source_id' => $decision->getKey(), 'code' => strtoupper($source), 'description' => $source === 'kip' ? 'Potongan KIP Kuliah' : 'Potongan beasiswa jalur masuk', 'amount' => '-'.Money::percent(Money::fromCents($grossCents), $discountPercent), 'installment_seq' => null, 'due_at' => null];
            $minimumCents = (int) round($minimumCents * (100 - $discountPercent) / 100);
        }

        return DB::transaction(function () use ($application, $items, $dueAt, $minimumCents): PmbInvoice {
            return $this->createInvoice($application, InvoiceType::InitialTuition, $dueAt, $items, Money::fromCents(max(0, $minimumCents)));
        });
    }

    /**
     * BR-18 / SM-04: transaksi sukses memperbarui tagihan dalam transaksi DB
     * yang sama (EC-05). Tagihan expired/cancelled → tetap dicatat, ditandai
     * `needs_resolution`, tidak otomatis mengaktifkan aplikasi.
     */
    public function applySuccessfulPayment(PaymentTransaction $transaction, Actor $actor): PmbInvoice
    {
        $invoice = PmbInvoice::query()->whereKey($transaction->invoice_id)->lockForUpdate()->firstOrFail();

        if (in_array($invoice->status, [PmbInvoiceStatus::Expired, PmbInvoiceStatus::Cancelled, PmbInvoiceStatus::Refunded], true)) {
            $invoice->forceFill(['needs_resolution' => true, 'resolution_note' => 'Pembayaran diterima setelah tagihan '.$invoice->status->value])->save();
            $this->outbox->notifyPermission($invoice, 'pmb_payments.finalize', 'pmb.ops.alert', ['message' => "Pembayaran terlambat untuk tagihan {$invoice->invoice_number} perlu resolusi (BR-18)."]);

            return $invoice;
        }

        $paid = Money::add((string) $invoice->amount_paid, (string) $transaction->amount);
        $target = Money::compare($paid, (string) $invoice->amount_net) >= 0 ? PmbInvoiceStatus::Paid : PmbInvoiceStatus::PartiallyPaid;
        $from = $invoice->status;

        if ($from !== $target) {
            StateTransition::assert($from, $target, 'tagihan');
        }

        $invoice->forceFill([
            'amount_paid' => $paid,
            'status' => $target,
            'paid_at' => $target === PmbInvoiceStatus::Paid ? ($transaction->paid_at ?? now()) : $invoice->paid_at,
            'lock_version' => $invoice->lock_version + 1,
        ])->save();

        $application = $invoice->application;
        $this->history->record($application, Lifecycle::RegistrationPayment, $from, $target, $actor, metadata: [
            'invoice_id' => $invoice->getKey(), 'invoice_type' => $invoice->type->value, 'transaction_id' => $transaction->getKey(), 'amount' => (string) $transaction->amount,
        ], auditSubject: $invoice);

        if ($invoice->type === InvoiceType::RegistrationFee) {
            $this->syncRegistrationStatus($application);
        }

        $this->outbox->notifyApplicant($application->account, 'pmb.payment.succeeded', [
            'amount' => Money::format((string) $transaction->amount),
            'invoice_number' => $invoice->invoice_number,
            'receipt_number' => (string) $transaction->receipt_number,
        ], aggregate: $transaction);

        return $invoice->refresh();
    }

    /** Transaksi gagal/kedaluwarsa/dibatalkan → tagihan kembali unpaid / partially_paid. */
    public function revertPending(PmbInvoice $invoice): PmbInvoice
    {
        $invoice = PmbInvoice::query()->whereKey($invoice->getKey())->lockForUpdate()->firstOrFail();
        $stillPending = PaymentTransaction::query()->where('invoice_id', $invoice->getKey())->where('status', TransactionStatus::Pending->value)->exists();

        if ($invoice->status === PmbInvoiceStatus::Pending && ! $stillPending) {
            $target = Money::isZero((string) $invoice->amount_paid) ? PmbInvoiceStatus::Unpaid : PmbInvoiceStatus::PartiallyPaid;
            $invoice->forceFill(['status' => $target])->save();

            if ($invoice->type === InvoiceType::RegistrationFee) {
                $this->syncRegistrationStatus($invoice->application);
            }
        }

        return $invoice;
    }

    public function markPending(PmbInvoice $invoice): void
    {
        if (in_array($invoice->status, [PmbInvoiceStatus::Unpaid, PmbInvoiceStatus::PartiallyPaid], true)) {
            $invoice->forceFill(['status' => PmbInvoiceStatus::Pending])->save();

            if ($invoice->type === InvoiceType::RegistrationFee) {
                $this->syncRegistrationStatus($invoice->application);
            }
        }
    }

    public function cancel(PmbInvoice $invoice, string $reason): void
    {
        DB::transaction(function () use ($invoice, $reason): void {
            $invoice = PmbInvoice::query()->whereKey($invoice->getKey())->lockForUpdate()->firstOrFail();

            if (! in_array($invoice->status, [PmbInvoiceStatus::Unpaid, PmbInvoiceStatus::Pending], true)) {
                return;
            }

            if (PaymentTransaction::query()->where('invoice_id', $invoice->getKey())->where('status', TransactionStatus::Success->value)->exists()) {
                return;
            }

            foreach (PaymentTransaction::query()->where('invoice_id', $invoice->getKey())->where('status', TransactionStatus::Pending->value)->get() as $transaction) {
                app(PaymentService::class)->cancelQuietly($transaction, 'Tagihan dibatalkan');
            }

            $invoice->forceFill(['status' => PmbInvoiceStatus::Cancelled, 'resolution_note' => mb_substr($reason, 0, 255)])->save();

            if ($invoice->type === InvoiceType::RegistrationFee) {
                $this->syncRegistrationStatus($invoice->application);
            }
        });
    }

    /** Job: tagihan lewat `due_at` tanpa lunas → expired (SM-04). */
    public function expireOverdue(): int
    {
        $count = 0;

        PmbInvoice::query()->whereIn('status', [PmbInvoiceStatus::Unpaid->value, PmbInvoiceStatus::Pending->value])->where('due_at', '<', now())
            ->chunkById(200, function ($invoices) use (&$count): void {
                foreach ($invoices as $invoice) {
                    DB::transaction(function () use ($invoice, &$count): void {
                        $invoice = PmbInvoice::query()->whereKey($invoice->getKey())->lockForUpdate()->first();

                        if ($invoice === null || ! in_array($invoice->status, [PmbInvoiceStatus::Unpaid, PmbInvoiceStatus::Pending], true)) {
                            return;
                        }

                        // Transaksi pending yang dibuat sebelum batas tetap boleh selesai (K.5): jangan expire selagi ada pending.
                        if (PaymentTransaction::query()->where('invoice_id', $invoice->getKey())->where('status', TransactionStatus::Pending->value)->exists()) {
                            return;
                        }

                        $invoice->forceFill(['status' => PmbInvoiceStatus::Expired])->save();

                        if ($invoice->type === InvoiceType::RegistrationFee) {
                            $this->syncRegistrationStatus($invoice->application);
                        }

                        $this->outbox->notifyApplicant($invoice->application->account, 'pmb.invoice.expired', ['invoice_number' => $invoice->invoice_number], aggregate: $invoice);
                        $count++;
                    });
                }
            });

        return $count;
    }

    /**
     * Voucher potongan (FR-PAY-05): berlaku per jenis tagihan & cakupan
     * jalur/gelombang, kuota pemakaian dikunci, maks 1x per akun.
     */
    public function applyVoucher(PmbInvoice $invoice, string $code): PmbInvoice
    {
        return DB::transaction(function () use ($invoice, $code): PmbInvoice {
            $invoice = PmbInvoice::query()->whereKey($invoice->getKey())->lockForUpdate()->firstOrFail();

            if (! in_array($invoice->status, [PmbInvoiceStatus::Unpaid, PmbInvoiceStatus::PartiallyPaid], true) || ! Money::isZero((string) $invoice->amount_paid)) {
                throw new DomainRuleException('Voucher hanya dapat dipakai pada tagihan yang belum dibayar dan tanpa transaksi berjalan.', 'INVOICE_NOT_PAYABLE', 409);
            }

            if ($invoice->items()->where('source_type', 'voucher')->exists()) {
                throw new DomainRuleException('Tagihan ini sudah memakai voucher.', 'VOUCHER_ALREADY_USED', 409);
            }

            $voucher = Voucher::query()->where('code', strtoupper(trim($code)))->lockForUpdate()->first();
            $application = $invoice->application;
            $scope = (array) ($voucher?->scope ?? []);
            $valid = $voucher !== null && $voucher->is_active
                && $voucher->applies_to === $invoice->type
                && now()->between($voucher->valid_from, $voucher->valid_until)
                && ($voucher->max_uses === null || $voucher->used_count < $voucher->max_uses)
                && (empty($scope['wave_path_ids']) || in_array($application->wave_path_id, (array) $scope['wave_path_ids'], true))
                && (empty($scope['admission_path_ids']) || in_array($application->admission_path_id, (array) $scope['admission_path_ids'], true));

            if (! $valid) {
                throw new DomainRuleException('Kode voucher tidak berlaku.', 'VOUCHER_INVALID', 422);
            }

            $usedByAccount = InvoiceItem::query()->where('source_type', 'voucher')->where('source_id', $voucher->getKey())
                ->whereHas('invoice.application', fn ($q) => $q->where('applicant_account_id', $application->applicant_account_id))->exists();

            if ($usedByAccount) {
                throw new DomainRuleException('Voucher ini sudah pernah Anda pakai.', 'VOUCHER_ALREADY_USED', 409);
            }

            $gross = (string) $invoice->amount_gross;
            $discount = $voucher->discount_type === 'percent' ? Money::percent($gross, (float) $voucher->discount_value) : (string) $voucher->discount_value;

            if ($voucher->max_discount !== null) {
                $discount = Money::min($discount, (string) $voucher->max_discount);
            }

            $discount = Money::min($discount, (string) $invoice->amount_net);

            InvoiceItem::query()->create([
                'university_id' => $invoice->university_id,
                'invoice_id' => $invoice->getKey(),
                'item_type' => 'discount',
                'source_type' => 'voucher',
                'source_id' => $voucher->getKey(),
                'code' => $voucher->code,
                'description' => 'Voucher '.$voucher->name,
                'amount' => '-'.$discount,
            ]);
            $voucher->increment('used_count');

            $this->recalculate($invoice);

            return $invoice->refresh();
        });
    }

    /** Σ item → gross/discount/net (aturan integritas 10 §4.11). */
    public function recalculate(PmbInvoice $invoice): void
    {
        $items = $invoice->items()->get();
        $gross = $items->where('item_type', 'charge')->sum(fn ($i) => Money::toCents((string) $i->amount));
        $discount = abs($items->where('item_type', 'discount')->sum(fn ($i) => Money::toCents((string) $i->amount)));
        $net = max(0, $gross - $discount);

        $attributes = ['amount_gross' => Money::fromCents($gross), 'amount_discount' => Money::fromCents($discount), 'amount_net' => Money::fromCents($net)];

        if ($invoice->minimum_for_enrollment !== null) {
            $attributes['minimum_for_enrollment'] = Money::fromCents((int) min($net, Money::toCents((string) $invoice->minimum_for_enrollment)));
        }

        if ($net === 0 && Money::isZero((string) $invoice->amount_paid)) {
            $attributes['status'] = PmbInvoiceStatus::Paid;
            $attributes['paid_at'] = now();
        }

        $invoice->forceFill($attributes)->save();

        if ($invoice->type === InvoiceType::RegistrationFee) {
            $this->syncRegistrationStatus($invoice->application);
        }
    }

    /** Cermin status tagihan pendaftaran di aplikasi (denormalisasi 10 §7). */
    public function syncRegistrationStatus(Application $application): void
    {
        if (Money::isZero((string) ($application->config_snapshot['registration_fee'] ?? '0'))) {
            $status = RegistrationPaymentStatus::NotRequired;
        } else {
            $invoice = PmbInvoice::query()->where('application_id', $application->getKey())->where('type', InvoiceType::RegistrationFee->value)
                ->whereNotIn('status', [PmbInvoiceStatus::Cancelled->value])->latest()->first();
            $status = match ($invoice?->status) {
                PmbInvoiceStatus::Paid => RegistrationPaymentStatus::Paid,
                PmbInvoiceStatus::Pending, PmbInvoiceStatus::PartiallyPaid => RegistrationPaymentStatus::Pending,
                PmbInvoiceStatus::Expired => RegistrationPaymentStatus::Expired,
                default => RegistrationPaymentStatus::Unpaid,
            };
        }

        if ($application->registration_payment_status !== $status) {
            Application::query()->whereKey($application->getKey())->update(['registration_payment_status' => $status->value]);
            $application->registration_payment_status = $status;
        }
    }

    /** Kuitansi PDF (FR-PAY-08) — dibangkitkan sekali per transaksi sukses, disimpan privat. */
    public function receipt(PmbInvoice $invoice): FileUpload
    {
        if (! in_array($invoice->status, [PmbInvoiceStatus::Paid, PmbInvoiceStatus::PartiallyPaid], true)) {
            throw new DomainRuleException('Kuitansi tersedia setelah pembayaran diterima.', 'INVOICE_NOT_PAID', 409);
        }

        $existing = FileUpload::query()->where('fileable_type', $invoice->getMorphClass())->where('fileable_id', $invoice->getKey())
            ->where('original_name', 'like', 'kuitansi-%')->latest()->first();
        $latestPaid = $invoice->transactions()->where('status', TransactionStatus::Success->value)->max('paid_at');

        if ($existing !== null && ($latestPaid === null || $existing->created_at->gte(CarbonImmutable::parse($latestPaid)))) {
            return $existing;
        }

        $invoice->loadMissing(['items', 'application.account', 'application.university']);
        $file = $this->pdf->render('admission::pdf.receipt', [
            'invoice' => $invoice,
            'transactions' => $invoice->transactions()->where('status', TransactionStatus::Success->value)->orderBy('paid_at')->get(),
            'university' => $invoice->application->university,
        ], 'kuitansi-'.$invoice->invoice_number.'.pdf', [$invoice->application->config_snapshot['period_code'] ?? 'pmb', (string) $invoice->application_id, 'receipts']);

        $file->forceFill(['fileable_type' => $invoice->getMorphClass(), 'fileable_id' => $invoice->getKey()])->save();

        return $file;
    }

    /**
     * Persentase potongan biaya awal: KIP terverifikasi = 100% (FR-SPC-02);
     * beasiswa jalur masuk dari keputusan (FR-SPC-06).
     */
    private function discountPercent(Application $application, AdmissionDecision $decision): float
    {
        if (in_array($application->kip_status, ['verified', 'accepted_by_program'], true)) {
            return 100.0;
        }

        $award = (array) ($decision->scholarship_award ?? []);

        return max(0.0, min(100.0, (float) ($award['percent'] ?? 0)));
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    private function createInvoice(Application $application, InvoiceType $type, CarbonImmutable $dueAt, array $items, ?string $minimum): PmbInvoice
    {
        $periodCode = (string) ($application->config_snapshot['period_code'] ?? 'PMB');
        $seq = $this->sequences->next('invoice_number', $periodCode, (string) $application->university_id);

        $invoice = PmbInvoice::query()->create([
            'university_id' => $application->university_id,
            'application_id' => $application->getKey(),
            'type' => $type,
            'invoice_number' => 'INV-'.$periodCode.'-'.SequenceGenerator::pad($seq, 7),
            'currency' => 'IDR',
            'amount_gross' => '0.00',
            'amount_discount' => '0.00',
            'amount_net' => '0.00',
            'amount_paid' => '0.00',
            'minimum_for_enrollment' => $minimum,
            'status' => PmbInvoiceStatus::Unpaid,
            'due_at' => $dueAt,
        ]);

        foreach ($items as $item) {
            InvoiceItem::query()->create([...$item, 'university_id' => $application->university_id, 'invoice_id' => $invoice->getKey()]);
        }

        $this->recalculate($invoice);
        $invoice->refresh();

        $this->outbox->notifyApplicant($application->account, 'pmb.invoice.created', [
            'invoice_number' => $invoice->invoice_number,
            'amount' => Money::format((string) $invoice->amount_net),
            'deadline' => $dueAt->toDateTimeString(),
        ], aggregate: $invoice);

        return $invoice;
    }
}
