<?php

namespace Modules\Admission\Services\Payment;

use App\Models\User;
use App\Support\Http\Exceptions\ConflictException;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Admission\Enums\InvoiceType;
use Modules\Admission\Enums\ManualProofStatus;
use Modules\Admission\Enums\PmbInvoiceStatus;
use Modules\Admission\Enums\TransactionStatus;
use Modules\Admission\Exceptions\DomainRuleException;
use Modules\Admission\Models\Application;
use Modules\Admission\Models\ManualPaymentProof;
use Modules\Admission\Models\PaymentTransaction;
use Modules\Admission\Models\PmbInvoice;
use Modules\Admission\Outbox\OutboxRecorder;
use Modules\Admission\Payments\GatewayStatus;
use Modules\Admission\Payments\GatewayUnavailable;
use Modules\Admission\Payments\PaymentGatewayManager;
use Modules\Admission\Support\Actor;
use Modules\Admission\Support\DocumentReasonCodes;
use Modules\Admission\Support\HistoryRecorder;
use Modules\Admission\Support\Money;
use Modules\Admission\Support\PmbFileStorage;
use Modules\Admission\Support\PmbSettings;
use Modules\Admission\Support\SequenceGenerator;
use Modules\Admission\Support\StateTransition;
use Modules\AuditLog\Enums\AuditAction;
use Throwable;

/**
 * Transaksi pembayaran (05 §F.2–F.5, SM-05, 11 §A.18.2). Status `success`
 * HANYA lewat (a) double-check API gateway atau (b) keputusan petugas
 * keuangan atas bukti/loket — tidak pernah dari klien (BR-15).
 */
class PaymentService
{
    public function __construct(
        private readonly PaymentGatewayManager $gateways,
        private readonly InvoiceService $invoices,
        private readonly OutboxRecorder $outbox,
        private readonly HistoryRecorder $history,
        private readonly PmbSettings $settings,
        private readonly SequenceGenerator $sequences,
        private readonly PmbFileStorage $storage,
    ) {}

    /** @return array<int, array<string, mixed>> */
    public function channels(): array
    {
        return array_values(array_filter($this->gateways->activeChannels(), fn ($c) => ($c['provider'] ?? '') !== 'cashier'));
    }

    /**
     * Membuat transaksi (11 §A.18.2): lock tagihan → payable & sebelum due →
     * batalkan transaksi pending lain (BR-16, untuk SEMUA kanal agar tidak
     * terjadi bayar ganda) → transaksi `created` → panggil adapter → `pending`.
     */
    public function createTransaction(PmbInvoice $invoice, string $channelCode, ?string $amount, string $idempotencyKey, Actor $actor): PaymentTransaction
    {
        $channel = $this->gateways->channel($channelCode) ?? throw ValidationException::withMessages(['channel' => 'Kanal pembayaran tidak tersedia.']);
        $provider = (string) $channel['provider'];

        if ($provider === 'cashier') {
            throw ValidationException::withMessages(['channel' => 'Pembayaran tunai dicatat oleh petugas loket.']);
        }

        $transaction = DB::transaction(function () use ($invoice, $channelCode, $provider, $amount, $idempotencyKey): PaymentTransaction {
            $invoice = PmbInvoice::query()->whereKey($invoice->getKey())->lockForUpdate()->firstOrFail();

            $existing = PaymentTransaction::query()->where('invoice_id', $invoice->getKey())->where('idempotency_key', $idempotencyKey)->first();

            if ($existing !== null) {
                return $existing;
            }

            if (! $invoice->status->isPayable() || $invoice->due_at->isPast()) {
                throw new DomainRuleException('Tagihan tidak dapat dibayar (lunas, kedaluwarsa, atau dibatalkan).', 'INVOICE_NOT_PAYABLE', 409);
            }

            $chargeAmount = $this->resolveAmount($invoice, $amount);

            foreach (PaymentTransaction::query()->where('invoice_id', $invoice->getKey())->where('status', TransactionStatus::Pending->value)->get() as $pending) {
                $this->cancelQuietly($pending, 'Diganti transaksi baru');
            }

            $uniqueCode = null;

            if ($provider === 'manual' && $this->settings->bool('pmb.payment.unique_code_enabled')) {
                // Kode unik 3 digit (rupiah) memudahkan pencocokan mutasi (FR-PAY-04).
                $uniqueCode = random_int(101, 999);
                $chargeAmount = Money::add($chargeAmount, (string) $uniqueCode);
            }

            return PaymentTransaction::query()->create([
                'university_id' => $invoice->university_id,
                'invoice_id' => $invoice->getKey(),
                'channel' => $channelCode,
                'provider' => $provider,
                'amount' => $chargeAmount,
                'currency' => 'IDR',
                'status' => TransactionStatus::Created,
                'idempotency_key' => $idempotencyKey,
                'unique_code' => $uniqueCode,
            ]);
        });

        if ($transaction->status !== TransactionStatus::Created) {
            return $transaction;
        }

        $ttlHours = max(1, $this->settings->int('pmb.payment.transaction_ttl_hours'));
        $expiresAt = CarbonImmutable::now()->addHours($ttlHours);
        $dueAt = CarbonImmutable::parse($invoice->due_at);
        $expiresAt = $expiresAt->gt($dueAt) && $dueAt->isFuture() ? $dueAt : $expiresAt;

        if ($provider === 'manual') {
            return DB::transaction(function () use ($transaction, $expiresAt, $invoice, $actor): PaymentTransaction {
                $transaction->forceFill([
                    'status' => TransactionStatus::Pending,
                    'provider_reference' => 'MAN-'.$transaction->getKey(),
                    'expires_at' => $expiresAt,
                    'instructions' => $this->manualInstructions($transaction),
                ])->save();
                $this->invoices->markPending($invoice->refresh());
                $this->notifyInstructions($transaction, $actor);

                return $transaction->refresh();
            });
        }

        try {
            $result = $this->gateways->gateway($provider)->createCharge($transaction, $transaction->channel, (string) $transaction->amount, (int) ceil(now()->diffInMinutes($expiresAt) / 60) ?: 1);
        } catch (Throwable $e) {
            $transaction->forceFill(['status' => TransactionStatus::Failed, 'failure_reason' => mb_substr($e->getMessage(), 0, 255)])->save();

            throw $e instanceof DomainRuleException ? $e : new GatewayUnavailable;
        }

        return DB::transaction(function () use ($transaction, $result, $expiresAt, $invoice, $actor): PaymentTransaction {
            $transaction->forceFill([
                'status' => TransactionStatus::Pending,
                'provider_reference' => $result->providerReference,
                'va_number' => $result->vaNumber,
                'qr_string' => $result->qrString,
                'redirect_url' => $result->redirectUrl,
                'expires_at' => $result->expiresAt ? CarbonImmutable::parse($result->expiresAt) : $expiresAt,
                'instructions' => $result->instructions,
                'provider_status_raw' => $result->rawStatus,
                'next_recheck_at' => now()->addMinutes(15),
            ])->save();
            $this->invoices->markPending($invoice->refresh());
            $this->notifyInstructions($transaction, $actor);

            return $transaction->refresh();
        });
    }

    /** FR-PAY-07: pendaftar membatalkan transaksi pending lalu memilih kanal lain. */
    public function cancel(PaymentTransaction $transaction): PaymentTransaction
    {
        return DB::transaction(function () use ($transaction): PaymentTransaction {
            $transaction = PaymentTransaction::query()->whereKey($transaction->getKey())->lockForUpdate()->firstOrFail();
            StateTransition::assert($transaction->status, TransactionStatus::Cancelled, 'transaksi');

            if ($transaction->manualProof?->status === ManualProofStatus::Submitted) {
                throw new DomainRuleException('Bukti transfer sedang diperiksa petugas; transaksi tidak dapat dibatalkan.', 'PROOF_UNDER_REVIEW', 409);
            }

            $this->cancelQuietly($transaction, 'Dibatalkan pendaftar');

            return $transaction->refresh();
        });
    }

    /** Pembatalan internal (BR-16, tagihan batal) — tanpa memeriksa pemilik. */
    public function cancelQuietly(PaymentTransaction $transaction, string $reason): void
    {
        if ($transaction->status !== TransactionStatus::Pending) {
            return;
        }

        if (! in_array($transaction->provider, PaymentGatewayManager::OFFLINE_PROVIDERS, true) && $transaction->provider_reference && $this->gateways->has($transaction->provider)) {
            // Pembatalan di gateway bila didukung; bila gagal tetap dicatat (SM-05 syarat cancelled).
            try {
                $this->gateways->gateway($transaction->provider)->cancel($transaction->provider_reference);
            } catch (Throwable) {
            }
        }

        $transaction->forceFill(['status' => TransactionStatus::Cancelled, 'failure_reason' => $reason])->save();
        $this->invoices->revertPending($transaction->invoice);
    }

    /**
     * Double-check status ke gateway lalu terapkan (callback, polling, tombol
     * "Cek status"/"Saya sudah bayar" — 11 §A.7, EC-03). Idempoten.
     */
    public function recheck(PaymentTransaction $transaction, Actor $actor): PaymentTransaction
    {
        if ($transaction->status !== TransactionStatus::Pending || in_array($transaction->provider, PaymentGatewayManager::OFFLINE_PROVIDERS, true)) {
            return $transaction;
        }

        $status = $this->gateways->gateway($transaction->provider)->getStatus((string) $transaction->provider_reference);

        return $this->applyGatewayStatus($transaction, $status, $actor);
    }

    /**
     * Menerapkan status tervalidasi gateway (05 §F.4): transisi hanya maju;
     * nominal/mata uang tidak cocok → tidak paid, masuk rekonsiliasi.
     *
     * @return PaymentTransaction transaksi terbaru
     */
    public function applyGatewayStatus(PaymentTransaction $transaction, GatewayStatus $status, Actor $actor, ?string $mismatchCallbackId = null): PaymentTransaction
    {
        return DB::transaction(function () use ($transaction, $status, $actor): PaymentTransaction {
            $transaction = PaymentTransaction::query()->whereKey($transaction->getKey())->lockForUpdate()->firstOrFail();

            if ($transaction->status !== TransactionStatus::Pending) {
                return $transaction;
            }

            $transaction->forceFill(['provider_status_raw' => $status->raw, 'recheck_attempts' => $transaction->recheck_attempts + 1, 'next_recheck_at' => now()->addMinutes(10)]);

            switch ($status->status) {
                case GatewayStatus::SUCCESS:
                    if ($status->amount === null || Money::compare($status->amount, (string) $transaction->amount) !== 0 || strtoupper($status->currency) !== $transaction->currency) {
                        $transaction->forceFill(['failure_reason' => 'AMOUNT_MISMATCH: gateway '.$status->amount.' '.$status->currency])->save();
                        app(ReconciliationService::class)->flagMismatch($transaction, (string) $status->amount);

                        return $transaction;
                    }

                    $transaction->forceFill([
                        'status' => TransactionStatus::Success,
                        'paid_at' => $status->paidAt ? CarbonImmutable::parse($status->paidAt, 'Asia/Jakarta') : now(),
                        'receipt_number' => $this->nextReceiptNumber($transaction),
                    ])->save();
                    $this->invoices->applySuccessfulPayment($transaction, $actor);
                    break;

                case GatewayStatus::FAILED:
                case GatewayStatus::EXPIRED:
                case GatewayStatus::CANCELLED:
                    $target = match ($status->status) {
                        GatewayStatus::EXPIRED => TransactionStatus::Expired,
                        GatewayStatus::CANCELLED => TransactionStatus::Cancelled,
                        default => TransactionStatus::Failed,
                    };
                    $transaction->forceFill(['status' => $target, 'failure_reason' => 'Gateway: '.$status->raw])->save();
                    $this->invoices->revertPending($transaction->invoice);
                    $this->outbox->notifyApplicant($transaction->invoice->application->account, $target === TransactionStatus::Expired ? 'pmb.payment.expired' : 'pmb.payment.failed', [
                        'invoice_number' => $transaction->invoice->invoice_number,
                    ], aggregate: $transaction);
                    break;

                default:
                    $transaction->save();
            }

            return $transaction->refresh();
        });
    }

    /**
     * Unggah bukti transfer manual (FR-PAY-04): satu bukti aktif per transaksi;
     * checksum sama dengan bukti lain → peringatan DUPLICATE_PROOF (F.5).
     *
     * @param  array{sender_bank: string, sender_name: string, transfer_date: string, transferred_amount: string|float}  $data
     */
    public function submitManualProof(PaymentTransaction $transaction, UploadedFile $file, array $data, Actor $actor): ManualPaymentProof
    {
        if ($transaction->provider !== 'manual' || $transaction->status !== TransactionStatus::Pending) {
            throw new DomainRuleException('Bukti hanya untuk transaksi transfer manual yang menunggu pembayaran.', 'INVALID_TRANSITION', 422);
        }

        if ($transaction->manualProof()->exists()) {
            throw new ConflictException('Bukti untuk transaksi ini sudah diunggah.', 'PROOF_EXISTS');
        }

        $upload = $this->storage->store($file, ['image/jpeg', 'image/png', 'application/pdf'], 2048, [
            $transaction->invoice->application->config_snapshot['period_code'] ?? 'pmb', (string) $transaction->invoice->application_id, 'payment-proofs',
        ]);

        return DB::transaction(function () use ($transaction, $upload, $data, $actor): ManualPaymentProof {
            $duplicate = ManualPaymentProof::query()->whereHas('fileUpload', fn ($q) => $q->where('checksum', $upload->checksum))->exists();

            $proof = ManualPaymentProof::query()->create([
                'university_id' => $transaction->university_id,
                'transaction_id' => $transaction->getKey(),
                'file_upload_id' => $upload->getKey(),
                'sender_bank' => $data['sender_bank'],
                'sender_name' => $data['sender_name'],
                'transfer_date' => $data['transfer_date'],
                'transferred_amount' => Money::fromCents(Money::toCents((string) $data['transferred_amount'])),
                'status' => ManualProofStatus::Submitted,
                'duplicate_warning' => $duplicate,
            ]);
            $upload->forceFill(['fileable_type' => $proof->getMorphClass(), 'fileable_id' => $proof->getKey()])->save();

            return $proof;
        });
    }

    /**
     * Keputusan petugas keuangan atas bukti manual (F.5): approved →
     * transaksi success (tanggal transfer), tagihan diperbarui; rejected →
     * transaksi failed (kode alasan), pendaftar dapat mencoba lagi.
     */
    public function decideManualProof(ManualPaymentProof $proof, bool $approve, ?string $reasonCode, ?string $note, User $actor): ManualPaymentProof
    {
        if (! $approve && ! in_array($reasonCode, DocumentReasonCodes::MANUAL_PROOF_REJECTION, true)) {
            throw ValidationException::withMessages(['reason_code' => 'Kode alasan penolakan wajib dipilih.']);
        }

        return DB::transaction(function () use ($proof, $approve, $reasonCode, $note, $actor): ManualPaymentProof {
            $proof = ManualPaymentProof::query()->whereKey($proof->getKey())->lockForUpdate()->firstOrFail();

            if ($proof->status !== ManualProofStatus::Submitted) {
                throw new ConflictException('Bukti ini sudah diputuskan.', 'ALREADY_DECIDED');
            }

            $transaction = PaymentTransaction::query()->whereKey($proof->transaction_id)->lockForUpdate()->firstOrFail();

            $proof->forceFill([
                'status' => $approve ? ManualProofStatus::Approved : ManualProofStatus::Rejected,
                'reason_code' => $approve ? null : $reasonCode,
                'note' => $note,
                'reviewed_by' => $actor->getKey(),
                'reviewed_at' => now(),
            ])->save();

            if ($approve) {
                if ($transaction->status !== TransactionStatus::Pending) {
                    throw new ConflictException('Transaksi tidak lagi menunggu pembayaran.', 'INVALID_TRANSITION');
                }

                $transaction->forceFill([
                    'status' => TransactionStatus::Success,
                    'paid_at' => CarbonImmutable::parse($proof->transfer_date)->endOfDay()->min(now()),
                    'recorded_by' => $actor->getKey(),
                    'receipt_number' => $this->nextReceiptNumber($transaction),
                ])->save();
                $this->invoices->applySuccessfulPayment($transaction, Actor::user($actor));
            } else {
                $transaction->forceFill(['status' => TransactionStatus::Failed, 'failure_reason' => 'Bukti ditolak: '.$reasonCode, 'recorded_by' => $actor->getKey()])->save();
                $this->invoices->revertPending($transaction->invoice);
                $this->outbox->notifyApplicant($transaction->invoice->application->account, 'pmb.payment.failed', ['invoice_number' => $transaction->invoice->invoice_number], aggregate: $transaction);
            }

            $this->history->action($proof, $approve ? AuditAction::Approved : AuditAction::Rejected, ['transaction_id' => $transaction->getKey(), 'reason_code' => $reasonCode], $note);

            return $proof->refresh();
        });
    }

    /**
     * Pembayaran tunai di loket (FR-PAY-04 kanal `cashier`) dicatat petugas
     * keuangan dengan nomor kuitansi.
     */
    public function recordCashierPayment(PmbInvoice $invoice, string $amount, string $receiptNumber, string $paidAt, User $actor): PaymentTransaction
    {
        return DB::transaction(function () use ($invoice, $amount, $receiptNumber, $paidAt, $actor): PaymentTransaction {
            $invoice = PmbInvoice::query()->whereKey($invoice->getKey())->lockForUpdate()->firstOrFail();

            if (! $invoice->status->isPayable()) {
                throw new DomainRuleException('Tagihan tidak dapat dibayar.', 'INVOICE_NOT_PAYABLE', 409);
            }

            $amount = Money::fromCents(Money::toCents($amount));
            $outstanding = $invoice->outstanding();

            if ($invoice->type === InvoiceType::RegistrationFee && Money::compare($amount, $outstanding) !== 0) {
                throw new DomainRuleException("Nominal harus sama dengan sisa tagihan ({$outstanding}).", 'AMOUNT_MISMATCH', 422);
            }

            if (Money::compare($amount, $outstanding) > 0 || Money::toCents($amount) <= 0) {
                throw new DomainRuleException('Nominal melebihi sisa tagihan atau tidak valid.', 'AMOUNT_MISMATCH', 422);
            }

            foreach (PaymentTransaction::query()->where('invoice_id', $invoice->getKey())->where('status', TransactionStatus::Pending->value)->get() as $pending) {
                $this->cancelQuietly($pending, 'Dibayar tunai di loket');
            }

            $transaction = PaymentTransaction::query()->create([
                'university_id' => $invoice->university_id,
                'invoice_id' => $invoice->getKey(),
                'channel' => 'cashier',
                'provider' => 'cashier',
                'amount' => $amount,
                'currency' => 'IDR',
                'status' => TransactionStatus::Success,
                'idempotency_key' => 'cashier-'.$receiptNumber,
                'provider_reference' => 'CSH-'.$receiptNumber,
                'paid_at' => CarbonImmutable::parse($paidAt),
                'receipt_number' => $receiptNumber,
                'recorded_by' => $actor->getKey(),
            ]);

            $this->invoices->applySuccessfulPayment($transaction, Actor::user($actor));
            $this->history->action($transaction, AuditAction::Created, ['channel' => 'cashier', 'amount' => $amount, 'receipt_number' => $receiptNumber]);

            return $transaction->refresh();
        });
    }

    /**
     * BR-18: resolusi pembayaran pada tagihan expired/cancelled — terima &
     * buka kembali (aplikasi lanjut) atau ajukan refund.
     */
    public function resolveLatePayment(PmbInvoice $invoice, string $resolution, string $note, User $actor): PmbInvoice
    {
        return DB::transaction(function () use ($invoice, $resolution, $note, $actor): PmbInvoice {
            $invoice = PmbInvoice::query()->whereKey($invoice->getKey())->lockForUpdate()->firstOrFail();

            if (! $invoice->needs_resolution) {
                throw new ConflictException('Tagihan tidak memerlukan resolusi.', 'NOT_NEEDED');
            }

            $successful = $invoice->transactions()->where('status', TransactionStatus::Success->value)->get();
            $paid = Money::add(...$successful->map(fn ($t) => (string) $t->amount)->all() ?: ['0']);

            if ($resolution === 'accept_and_reopen') {
                if (! $actor->hasPermissionTo('pmb_verification.finalize') && ! $actor->hasPermissionTo('pmb_config.update')) {
                    abort(403, 'Membuka kembali aplikasi memerlukan persetujuan Admin PMB.');
                }

                $target = Money::compare($paid, (string) $invoice->amount_net) >= 0 ? PmbInvoiceStatus::Paid : PmbInvoiceStatus::PartiallyPaid;
                $invoice->forceFill(['status' => $target, 'amount_paid' => $paid, 'paid_at' => $successful->max('paid_at'), 'needs_resolution' => false, 'resolution_note' => $note])->save();

                if ($invoice->type === InvoiceType::RegistrationFee) {
                    $this->invoices->syncRegistrationStatus($invoice->application);
                }
            } else {
                foreach ($successful as $transaction) {
                    app(RefundService::class)->request($transaction, [
                        'amount' => (string) $transaction->amount,
                        'reason_code' => 'LATE_PAYMENT',
                        'reason_note' => $note,
                        'beneficiary_bank' => '-',
                        'beneficiary_account' => '-',
                        'beneficiary_name' => $invoice->application->account->name,
                    ], $actor);
                }

                $invoice->forceFill(['needs_resolution' => false, 'resolution_note' => $note])->save();
            }

            $this->history->action($invoice, AuditAction::Updated, ['resolution' => $resolution], $note);

            return $invoice->refresh();
        });
    }

    /**
     * Job: transaksi pending lewat `expires_at` → double-check gateway dulu
     * (bisa saja sudah dibayar), lalu expired.
     */
    public function expireOverdueTransactions(): int
    {
        $count = 0;

        foreach (PaymentTransaction::query()->withoutGlobalScopes()->where('status', TransactionStatus::Pending->value)->where('expires_at', '<', now())->limit(500)->get() as $transaction) {
            app(\App\Support\Tenancy\TenantContext::class)->setUniversityId((string) $transaction->university_id);

            if ($transaction->manualProof()->where('status', ManualProofStatus::Submitted->value)->exists()) {
                continue;
            }

            if (! in_array($transaction->provider, PaymentGatewayManager::OFFLINE_PROVIDERS, true) && $this->gateways->has($transaction->provider)) {
                try {
                    $after = $this->recheck($transaction, Actor::system());

                    if ($after->status !== TransactionStatus::Pending) {
                        $count++;

                        continue;
                    }
                } catch (Throwable) {
                    continue;
                }
            }

            DB::transaction(function () use ($transaction): void {
                $locked = PaymentTransaction::query()->whereKey($transaction->getKey())->lockForUpdate()->first();

                if ($locked?->status === TransactionStatus::Pending) {
                    $locked->forceFill(['status' => TransactionStatus::Expired])->save();
                    $this->invoices->revertPending($locked->invoice);
                    $this->outbox->notifyApplicant($locked->invoice->application->account, 'pmb.payment.expired', ['invoice_number' => $locked->invoice->invoice_number], aggregate: $locked);
                }
            });
            $count++;
        }

        return $count;
    }

    /** Polling cadangan tiap 10 menit untuk transaksi pending > 15 menit (05 §F.4 aturan 6). */
    public function pollPending(): int
    {
        $count = 0;
        $transactions = PaymentTransaction::query()->withoutGlobalScopes()->where('status', TransactionStatus::Pending->value)
            ->whereNotIn('provider', PaymentGatewayManager::OFFLINE_PROVIDERS)
            ->where('created_at', '<', now()->subMinutes(15))
            ->where(fn ($q) => $q->whereNull('next_recheck_at')->orWhere('next_recheck_at', '<=', now()))
            ->limit(200)->get();

        foreach ($transactions as $transaction) {
            app(\App\Support\Tenancy\TenantContext::class)->setUniversityId((string) $transaction->university_id);

            try {
                if ($this->gateways->has($transaction->provider)) {
                    $this->recheck($transaction, Actor::system());
                    $count++;
                }
            } catch (Throwable) {
                $transaction->forceFill(['next_recheck_at' => now()->addMinutes(15)])->save();
            }
        }

        return $count;
    }

    private function resolveAmount(PmbInvoice $invoice, ?string $amount): string
    {
        $outstanding = $invoice->outstanding();

        if ($invoice->type === InvoiceType::RegistrationFee || $amount === null || $amount === '') {
            return $outstanding;
        }

        // Biaya awal boleh dibayar sebagian (cicilan): minimal cicilan berikutnya, maksimal sisa.
        $amount = Money::fromCents(Money::toCents($amount));
        $minimum = Money::max('0.00', Money::sub((string) $invoice->minimum_for_enrollment, (string) $invoice->amount_paid));
        $minimum = Money::isZero($minimum) ? Money::min('1.00', $outstanding) : Money::min($minimum, $outstanding);

        if (Money::compare($amount, $outstanding) > 0 || Money::compare($amount, $minimum) < 0) {
            throw ValidationException::withMessages(['amount' => "Nominal harus antara {$minimum} dan {$outstanding}."]);
        }

        return $amount;
    }

    private function nextReceiptNumber(PaymentTransaction $transaction): string
    {
        $year = now()->format('Y');

        return 'KW-'.$year.'-'.SequenceGenerator::pad($this->sequences->next('receipt_number', $year, (string) $transaction->university_id), 7);
    }

    /** @return array<int, array{title: string, steps: array<int, string>}> */
    private function manualInstructions(PaymentTransaction $transaction): array
    {
        $accounts = (array) $this->settings->get('pmb.payment.bank_accounts');
        $amount = Money::format((string) $transaction->amount);
        $steps = [];

        foreach ($accounts as $account) {
            $steps[] = ($account['bank'] ?? 'Bank').' '.($account['number'] ?? '').' a.n. '.($account['holder'] ?? '');
        }

        return [[
            'title' => 'Transfer Bank',
            'steps' => [
                "Transfer TEPAT sebesar {$amount}".($transaction->unique_code ? ' (termasuk kode unik '.$transaction->unique_code.')' : '').' ke salah satu rekening berikut:',
                ...($steps ?: ['Rekening kampus belum diatur — hubungi panitia.']),
                'Unggah bukti transfer beserta nama pengirim, bank asal, dan tanggal transfer.',
                'Petugas keuangan memverifikasi paling lambat 1 hari kerja.',
            ],
        ]];
    }

    private function notifyInstructions(PaymentTransaction $transaction, Actor $actor): void
    {
        $application = Application::query()->with('account')->findOrFail($transaction->invoice->application_id);

        $this->outbox->notifyApplicant($application->account, 'pmb.payment.instructions', [
            'amount' => Money::format((string) $transaction->amount),
            'channel' => $transaction->channel,
            'deadline' => optional($transaction->expires_at)->toDateTimeString() ?? '-',
        ], aggregate: $transaction);
    }
}
