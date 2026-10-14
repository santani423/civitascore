<?php

namespace Modules\Admission\Services\Payment;

use App\Models\User;
use App\Support\Http\Exceptions\ConflictException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Admission\Enums\ChangeRequestStatus;
use Modules\Admission\Enums\ChangeRequestType;
use Modules\Admission\Enums\InvoiceType;
use Modules\Admission\Enums\PmbInvoiceStatus;
use Modules\Admission\Enums\RefundStatus;
use Modules\Admission\Enums\TransactionStatus;
use Modules\Admission\Models\ChangeRequest;
use Modules\Admission\Models\ManualPaymentProof;
use Modules\Admission\Models\PaymentTransaction;
use Modules\Admission\Models\PmbInvoice;
use Modules\Admission\Models\Refund;
use Modules\Admission\Models\WavePath;
use Modules\Admission\Outbox\OutboxRecorder;
use Modules\Admission\Payments\GatewayUnsupported;
use Modules\Admission\Payments\PaymentGatewayManager;
use Modules\Admission\Services\Approval\PmbApprovalService;
use Modules\Admission\Support\DocumentReasonCodes;
use Modules\Admission\Support\HistoryRecorder;
use Modules\Admission\Support\Money;
use Modules\AuditLog\Enums\AuditAction;
use Throwable;

/**
 * Pengembalian dana (05 §F.7, FR-PAY-10, BR-19/20): pengajuan oleh
 * finance_officer → approval engine (`pmb_refund`) → pelaksanaan (API
 * gateway bila didukung, selain itu transfer di luar sistem + bukti).
 */
class RefundService
{
    public function __construct(
        private readonly PmbApprovalService $approvals,
        private readonly PaymentGatewayManager $gateways,
        private readonly HistoryRecorder $history,
        private readonly OutboxRecorder $outbox,
    ) {}

    /**
     * @param  array{amount: string, reason_code: string, reason_note: string, beneficiary_bank: string, beneficiary_account: string, beneficiary_name: string}  $data
     */
    public function request(PaymentTransaction $transaction, array $data, User $actor, ?Refund $draft = null): Refund
    {
        if ($transaction->status !== TransactionStatus::Success) {
            throw new ConflictException('Refund hanya untuk transaksi yang berhasil.', 'INVALID_TRANSITION');
        }

        if (! in_array($data['reason_code'], DocumentReasonCodes::REFUND, true)) {
            throw ValidationException::withMessages(['reason_code' => 'Kode alasan refund tidak dikenal.']);
        }

        // BR-20: verifikator bukti bayar manual ≠ pengaju refund transaksi yang sama.
        $proofReviewer = ManualPaymentProof::query()->where('transaction_id', $transaction->getKey())->value('reviewed_by');

        if ($proofReviewer !== null && (string) $proofReviewer === (string) $actor->getKey()) {
            abort(403, 'Verifikator bukti bayar tidak boleh mengajukan refund untuk transaksi yang sama (BR-20).');
        }

        $alreadyRefunded = Refund::query()->where('transaction_id', $transaction->getKey())
            ->whereNotIn('status', [RefundStatus::Rejected->value, RefundStatus::Failed->value])
            ->when($draft, fn ($q) => $q->whereKeyNot($draft->getKey()))->get()
            ->sum(fn (Refund $r) => Money::toCents((string) $r->amount));
        $amount = Money::fromCents(Money::toCents($data['amount']));
        $maximum = Money::fromCents(Money::toCents((string) $transaction->amount) - $alreadyRefunded);

        if (Money::toCents($amount) <= 0 || Money::compare($amount, $maximum) > 0) {
            throw ValidationException::withMessages(['amount' => "Nominal refund maksimal {$maximum}."]);
        }

        return DB::transaction(function () use ($transaction, $data, $amount, $actor, $draft): Refund {
            $refund = $draft ?? new Refund;
            $refund->forceFill([
                'university_id' => $transaction->university_id,
                'transaction_id' => $transaction->getKey(),
                'amount' => $amount,
                'reason_code' => $data['reason_code'],
                'reason_note' => $data['reason_note'],
                'beneficiary_bank' => $data['beneficiary_bank'],
                'beneficiary_account' => $data['beneficiary_account'],
                'beneficiary_name' => $data['beneficiary_name'],
                'status' => RefundStatus::PendingApproval,
                'requested_by' => $actor->getKey(),
            ])->save();

            $approval = $this->approvals->submit($refund, PmbApprovalService::REFUND, $actor, $data['reason_note']);
            $refund->forceFill(['approval_request_id' => $approval->getKey()])->save();

            $invoice = PmbInvoice::query()->whereKey($transaction->invoice_id)->lockForUpdate()->firstOrFail();

            if (in_array($invoice->status, [PmbInvoiceStatus::Paid, PmbInvoiceStatus::PartiallyPaid], true)) {
                $invoice->forceFill(['status' => PmbInvoiceStatus::RefundPending])->save();
            }

            $this->history->action($refund, AuditAction::Created, ['amount' => $amount, 'reason_code' => $data['reason_code']], $data['reason_note']);

            return $refund->refresh();
        });
    }

    /** Listener approval: disetujui → coba refund via API gateway; bila tidak didukung, menunggu pelaksanaan manual. */
    public function onApproved(Refund $refund): void
    {
        DB::transaction(function () use ($refund): void {
            $refund = Refund::query()->whereKey($refund->getKey())->lockForUpdate()->firstOrFail();

            if ($refund->status !== RefundStatus::PendingApproval) {
                return;
            }

            $refund->forceFill(['status' => RefundStatus::Approved])->save();
        });

        $transaction = $refund->transaction;

        if (! in_array($transaction->provider, PaymentGatewayManager::OFFLINE_PROVIDERS, true) && $this->gateways->has($transaction->provider)) {
            try {
                $refund->forceFill(['status' => RefundStatus::Processing])->save();

                if ($this->gateways->gateway($transaction->provider)->refund((string) $transaction->provider_reference, (string) $refund->amount, $refund->reason_note)) {
                    $this->finalize($refund->refresh(), null, null);

                    return;
                }
            } catch (GatewayUnsupported) {
            } catch (Throwable) {
            }

            $refund->forceFill(['status' => RefundStatus::Approved])->save();
        }
    }

    public function onRejected(Refund $refund): void
    {
        DB::transaction(function () use ($refund): void {
            $refund = Refund::query()->whereKey($refund->getKey())->lockForUpdate()->firstOrFail();

            if ($refund->status !== RefundStatus::PendingApproval) {
                return;
            }

            $refund->forceFill(['status' => RefundStatus::Rejected])->save();
            $this->restoreInvoiceStatus($refund->transaction->invoice);
        });
    }

    /** Pencatatan selesai oleh keuangan dengan bukti transfer (11 §A.7 `complete`). */
    public function complete(Refund $refund, ?string $proofFileId, ?string $completedAt, User $actor): Refund
    {
        if (! in_array($refund->status, [RefundStatus::Approved, RefundStatus::Processing], true)) {
            throw new ConflictException('Refund belum disetujui.', 'INVALID_TRANSITION');
        }

        return $this->finalize($refund, $proofFileId, $completedAt, $actor);
    }

    /**
     * Refund massal saat gelombang-jalur dibatalkan kampus (F.7): satu
     * persetujuan untuk seluruh batch (`pmb_change_requests` tipe bulk_refund).
     */
    public function requestBulkForWavePath(WavePath $wavePath, string $reason, User $actor): ChangeRequest
    {
        $change = ChangeRequest::query()->create([
            'type' => ChangeRequestType::BulkRefund,
            'subject_type' => 'WavePath',
            'subject_id' => $wavePath->getKey(),
            'payload' => ['wave_path_id' => $wavePath->getKey()],
            'reason' => $reason,
            'status' => ChangeRequestStatus::PendingApproval,
            'requested_by' => $actor->getKey(),
        ]);
        $approval = $this->approvals->submit($change, PmbApprovalService::REFUND, $actor, $reason);
        $change->forceFill(['approval_request_id' => $approval->getKey()])->save();

        return $change;
    }

    public function applyBulk(ChangeRequest $change): void
    {
        $transactions = PaymentTransaction::query()->where('status', TransactionStatus::Success->value)
            ->whereHas('invoice', fn ($q) => $q->where('type', InvoiceType::RegistrationFee->value)
                ->whereHas('application', fn ($a) => $a->where('wave_path_id', $change->payload['wave_path_id'] ?? '')))
            ->get();

        foreach ($transactions as $transaction) {
            if (Refund::query()->where('transaction_id', $transaction->getKey())->whereNotIn('status', ['rejected', 'failed'])->exists()) {
                continue;
            }

            $refund = Refund::query()->create([
                'university_id' => $transaction->university_id,
                'transaction_id' => $transaction->getKey(),
                'amount' => (string) $transaction->amount,
                'reason_code' => 'PERIOD_CANCELLED',
                'reason_note' => $change->reason,
                'beneficiary_bank' => '-',
                'beneficiary_account' => '-',
                'beneficiary_name' => $transaction->invoice->application->account->name,
                'status' => RefundStatus::Approved,
                'requested_by' => $change->requested_by,
                'batch_reference' => $change->getKey(),
            ]);
            $transaction->invoice->forceFill(['status' => PmbInvoiceStatus::RefundPending])->save();
            $this->history->action($refund, AuditAction::Created, ['batch' => $change->getKey()], $change->reason);
        }

        $change->forceFill(['status' => ChangeRequestStatus::Applied, 'applied_at' => now()])->save();
    }

    private function finalize(Refund $refund, ?string $proofFileId, ?string $completedAt, ?User $actor = null): Refund
    {
        return DB::transaction(function () use ($refund, $proofFileId, $completedAt, $actor): Refund {
            $refund = Refund::query()->whereKey($refund->getKey())->lockForUpdate()->firstOrFail();
            $refund->forceFill([
                'status' => RefundStatus::Completed,
                'completed_at' => $completedAt ?? now(),
                'proof_file_id' => $proofFileId,
            ])->save();

            $transaction = PaymentTransaction::query()->whereKey($refund->transaction_id)->lockForUpdate()->firstOrFail();
            $refundedTotal = Refund::query()->where('transaction_id', $transaction->getKey())->where('status', RefundStatus::Completed->value)->get()
                ->sum(fn (Refund $r) => Money::toCents((string) $r->amount));

            if ($refundedTotal >= Money::toCents((string) $transaction->amount) && $transaction->status === TransactionStatus::Success) {
                $transaction->forceFill(['status' => TransactionStatus::Refunded])->save();
            }

            $invoice = PmbInvoice::query()->whereKey($transaction->invoice_id)->lockForUpdate()->firstOrFail();
            $paid = Money::sub((string) $invoice->amount_paid, (string) $refund->amount);
            $invoice->forceFill(['amount_paid' => Money::max('0.00', $paid)])->save();

            $stillPendingRefund = Refund::query()->whereHas('transaction', fn ($q) => $q->where('invoice_id', $invoice->getKey()))
                ->whereIn('status', [RefundStatus::PendingApproval->value, RefundStatus::Approved->value, RefundStatus::Processing->value])->exists();

            if (! $stillPendingRefund) {
                $invoice->forceFill(['status' => Money::isZero((string) $invoice->amount_paid) ? PmbInvoiceStatus::Refunded : (Money::compare((string) $invoice->amount_paid, (string) $invoice->amount_net) >= 0 ? PmbInvoiceStatus::Paid : PmbInvoiceStatus::PartiallyPaid)])->save();
            }

            if ($invoice->type === InvoiceType::RegistrationFee) {
                app(InvoiceService::class)->syncRegistrationStatus($invoice->application);
            }

            $this->history->action($refund, AuditAction::Updated, ['status' => 'completed', 'by' => $actor?->getKey()]);
            $this->outbox->notifyApplicant($invoice->application->account, 'pmb.refund.completed', ['amount' => Money::format((string) $refund->amount)], aggregate: $refund);

            return $refund->refresh();
        });
    }

    private function restoreInvoiceStatus(PmbInvoice $invoice): void
    {
        if ($invoice->status !== PmbInvoiceStatus::RefundPending) {
            return;
        }

        $invoice->forceFill(['status' => Money::compare((string) $invoice->amount_paid, (string) $invoice->amount_net) >= 0 ? PmbInvoiceStatus::Paid : PmbInvoiceStatus::PartiallyPaid])->save();
    }
}
