<?php

namespace Modules\Finance\Services;

use App\Models\User;
use App\Support\Http\Exceptions\ConflictException;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Modules\Academic\Models\Student;
use Modules\AuditLog\Enums\AuditAction;
use Modules\AuditLog\Services\AuditLogService;
use Modules\Finance\Enums\DepositEntryType;
use Modules\Finance\Enums\InvoiceStatus;
use Modules\Finance\Enums\PaymentMethod;
use Modules\Finance\Enums\ReconciliationStatus;
use Modules\Finance\Enums\TransactionStatus;
use Modules\Finance\Models\Invoice;
use Modules\Finance\Models\InvoiceInstallment;
use Modules\Finance\Models\Payment;
use Modules\Finance\Models\PaymentTransaction;
use Modules\Finance\Models\StudentDepositEntry;
use Modules\Finance\Support\FinanceNumbering;
use Modules\Finance\Support\Money;

/**
 * Jalur tunggal uang masuk (BR-KEU-41): menandai transaksi `success`,
 * mengalokasikan ke tagihan/termin, mengkreditkan kelebihan ke deposit
 * (BR-KEU-46/47), dan menghitung ulang status (InvoiceLedger). Idempoten:
 * transaksi yang sudah `success` tidak dialokasikan dua kali.
 */
class PaymentPostingService
{
    public function __construct(
        private readonly InvoiceLedger $ledger,
        private readonly DepositService $deposits,
        private readonly FinanceNumbering $numbering,
        private readonly PenaltyService $penalties,
        private readonly FinanceNotifier $notifier,
        private readonly AuditLogService $audit,
    ) {}

    /**
     * @param  string  $receivedAmount  nominal yang benar-benar diterima (bukti/penyedia/kasir)
     */
    public function settle(PaymentTransaction $transaction, string $receivedAmount, CarbonImmutable $paidAt, ?User $actor = null): PaymentTransaction
    {
        $result = DB::transaction(function () use ($transaction, $receivedAmount, $paidAt, $actor): array {
            /** @var PaymentTransaction $tx */
            $tx = PaymentTransaction::query()->lockForUpdate()->findOrFail($transaction->id);

            if ($tx->status === TransactionStatus::Success) {
                return [$tx, [], 0];
            }

            if (in_array($tx->status, [TransactionStatus::Reversed, TransactionStatus::Refunded], true)) {
                throw new ConflictException('Transaksi ini sudah dibalik dan tidak dapat diproses ulang.');
            }

            $late = in_array($tx->status, [TransactionStatus::Expired, TransactionStatus::Cancelled, TransactionStatus::Failed], true);
            $received = Money::toCents($receivedAmount);

            if ($received <= 0) {
                throw new ConflictException('Nominal pembayaran harus lebih dari nol.');
            }

            $tx->forceFill([
                'amount' => Money::fromCents($received),
                'status' => TransactionStatus::Success,
                'paid_at' => $paidAt,
                'receipt_number' => $tx->receipt_number ?? $this->numbering->next('receipt', $paidAt->format('Ym'), $tx->university_id),
                'reconciliation_status' => match ($tx->method) {
                    PaymentMethod::Deposit => ReconciliationStatus::NotRequired,
                    PaymentMethod::Cash => ReconciliationStatus::PendingDeposit,
                    default => ReconciliationStatus::Unreconciled,
                },
                'needs_resolution' => $late,
                'lock_version' => $tx->lock_version + 1,
            ])->save();

            $available = $received - Money::toCents($tx->channel_fee);
            [$touched, $used] = $this->allocate($tx, $available, $actor);
            $leftover = $available - $used;
            $student = Student::query()->findOrFail($tx->student_id);

            if ($leftover > 0 && $tx->method !== PaymentMethod::Deposit) {
                $type = $tx->target_type === PaymentTransaction::TARGET_DEPOSIT ? DepositEntryType::TopUp : DepositEntryType::Overpayment;
                $this->deposits->credit($student, $leftover, $type, 'payment_transaction', $tx->id, $tx->transaction_number, $actor?->id);

                if ($tx->target_type !== PaymentTransaction::TARGET_DEPOSIT && $used === 0) {
                    $tx->forceFill(['needs_resolution' => true])->save();
                }
            }

            return [$tx, $touched, $leftover];
        });

        [$tx, $touched, $leftover] = $result;

        if ($touched !== [] || $leftover > 0) {
            $this->afterSettlement($tx, $touched, $leftover);
        }

        return $tx->fresh();
    }

    /**
     * Membayar tagihan/termin dari saldo deposit (FR-DEP-03). Nominal default
     * = min(saldo tersedia, sisa target).
     */
    public function applyDeposit(Student $student, Invoice $invoice, ?string $amount, ?InvoiceInstallment $installment, ?User $actor): PaymentTransaction
    {
        return DB::transaction(function () use ($student, $invoice, $amount, $installment, $actor): PaymentTransaction {
            $locked = $this->ledger->lock($invoice->id);

            if ($locked->student_id !== $student->id) {
                throw new ConflictException('Tagihan bukan milik mahasiswa ini.');
            }

            if (! $locked->status->isOpen()) {
                throw new ConflictException('Tagihan ini tidak memiliki sisa kewajiban.');
            }

            $outstanding = Money::toCents($locked->outstanding());
            $available = $this->deposits->available($student);
            $cents = $amount !== null ? Money::toCents($amount) : min($available, $outstanding);
            $cents = min($cents, $outstanding);

            if ($cents <= 0) {
                throw new ConflictException('Saldo deposit kosong atau tagihan sudah lunas.', 'INSUFFICIENT_DEPOSIT');
            }

            $tx = PaymentTransaction::query()->create([
                'university_id' => $student->university_id,
                'transaction_number' => $this->numbering->next('transaction', null, $student->university_id),
                'student_id' => $student->id,
                'provider' => 'deposit',
                'method' => PaymentMethod::Deposit,
                'target_type' => $installment ? PaymentTransaction::TARGET_INSTALLMENT : PaymentTransaction::TARGET_INVOICE,
                'target_id' => $installment?->id ?? $locked->id,
                'amount' => Money::fromCents($cents),
                'status' => TransactionStatus::Created,
                'recorded_by' => $actor?->id,
                'reconciliation_status' => ReconciliationStatus::NotRequired,
            ]);

            $this->deposits->debit($student, $cents, DepositEntryType::Allocation, 'payment_transaction', $tx->id, $locked->invoice_number, $actor?->id);

            return $this->settle($tx, (string) $tx->amount, CarbonImmutable::now(), $actor);
        });
    }

    /**
     * Pembalikan transaksi sukses (BR-KEU-61): alokasi dibalik, kredit deposit
     * yang lahir dari transaksi ini ditarik kembali, status dihitung ulang.
     * Pencatat/verifikator transaksi tidak boleh membalik (SoD).
     */
    public function reverse(PaymentTransaction $transaction, string $reason, User $actor): PaymentTransaction
    {
        $tx = DB::transaction(function () use ($transaction, $reason, $actor): PaymentTransaction {
            $tx = PaymentTransaction::query()->lockForUpdate()->findOrFail($transaction->id);

            if ($tx->status !== TransactionStatus::Success) {
                throw new ConflictException('Hanya transaksi berhasil yang dapat dibalik.');
            }

            if (in_array((string) $actor->id, array_filter([$tx->recorded_by, $tx->verified_by]), true)) {
                throw new ConflictException('Pencatat atau verifikator transaksi tidak dapat membalik transaksi yang sama (pemisahan tugas).', 'SELF_APPROVAL_FORBIDDEN');
            }

            $student = Student::query()->findOrFail($tx->student_id);
            $credited = (int) StudentDepositEntry::query()
                ->where('reference_type', 'payment_transaction')->where('reference_id', $tx->id)
                ->whereIn('entry_type', [DepositEntryType::Overpayment->value, DepositEntryType::TopUp->value])
                ->get(['amount'])->sum(fn (StudentDepositEntry $entry) => Money::toCents($entry->amount));

            if ($credited > 0) {
                $this->deposits->debit($student, $credited, DepositEntryType::Reversal, 'payment_transaction', $tx->id, 'Pembalikan '.$tx->transaction_number, $actor->id);
            }

            if ($tx->method === PaymentMethod::Deposit) {
                $this->deposits->credit($student, Money::toCents($tx->amount), DepositEntryType::Reversal, 'payment_transaction', $tx->id, 'Pembalikan '.$tx->transaction_number, $actor->id);
            }

            $invoiceIds = $tx->allocations()->where('status', Payment::POSTED)->pluck('invoice_id')->unique();
            $tx->allocations()->where('status', Payment::POSTED)->update([
                'status' => Payment::REVERSED,
                'reversed_at' => now(),
                'reversed_by' => $actor->id,
                'reversal_reason' => $reason,
            ]);

            foreach ($invoiceIds as $invoiceId) {
                $this->ledger->recalculate($this->ledger->lock($invoiceId));
            }

            $tx->forceFill(['status' => TransactionStatus::Reversed, 'reversed_at' => now(), 'reversal_reason' => $reason, 'lock_version' => $tx->lock_version + 1])->save();
            $this->audit->recordAction($tx, AuditAction::ForcedOverride, ['action' => 'reverse', 'amount' => (string) $tx->amount], $reason);

            return $tx;
        });

        $this->notifier->student($tx->student, 'keu.payment_reversed', ['receipt' => (string) $tx->receipt_number, 'amount' => Money::format($tx->amount), 'reason' => $reason]);

        return $tx;
    }

    /**
     * @return array{0: array<int, string>, 1: int} [id tagihan tersentuh, sen terpakai]
     */
    private function allocate(PaymentTransaction $tx, int $available, ?User $actor): array
    {
        if ($available <= 0 || $tx->target_type === PaymentTransaction::TARGET_DEPOSIT) {
            return [[], 0];
        }

        $installment = $tx->target_type === PaymentTransaction::TARGET_INSTALLMENT ? InvoiceInstallment::query()->find($tx->target_id) : null;
        $invoiceId = $installment?->invoice_id ?? $tx->target_id;

        if ($invoiceId === null) {
            return [[], 0];
        }

        $invoice = $this->ledger->lock($invoiceId);

        if (! $invoice->status->isOpen()) {
            return [[], 0];
        }

        $used = $this->allocateToInvoice($invoice, $available, $tx, $installment?->id, $actor);
        $this->ledger->recalculate($invoice);

        return [[$invoice->id], $used];
    }

    private function allocateToInvoice(Invoice $invoice, int $cents, PaymentTransaction $tx, ?string $preferredInstallmentId, ?User $actor): int
    {
        $outstanding = Money::toCents($invoice->amount) - Money::toCents($invoice->paid_amount);
        $budget = min($cents, $outstanding);

        if ($budget <= 0) {
            return 0;
        }

        $installments = InvoiceInstallment::query()->where('invoice_id', $invoice->id)->whereNull('superseded_at')->orderBy('seq')->lockForUpdate()->get();

        if ($installments->isEmpty()) {
            $this->postAllocation($invoice, null, $budget, $tx, $actor);

            return $budget;
        }

        if ($preferredInstallmentId !== null) {
            $installments = $installments->sortBy(fn (InvoiceInstallment $i) => $i->id === $preferredInstallmentId ? -1 : $i->seq)->values();
        }

        $left = $budget;

        foreach ($installments as $installment) {
            $remaining = Money::toCents($installment->amount) - Money::toCents($installment->paid_amount);

            if ($remaining <= 0 || $left <= 0) {
                continue;
            }

            $portion = min($remaining, $left);
            $this->postAllocation($invoice, $installment->id, $portion, $tx, $actor);
            $installment->forceFill(['paid_amount' => Money::add($installment->paid_amount, Money::fromCents($portion))])->save();
            $left -= $portion;
        }

        if ($left > 0) {
            // Termin sudah lunas semua tetapi tagihan masih bersisa (pembulatan/penyesuaian): alokasi tanpa termin.
            $this->postAllocation($invoice, null, $left, $tx, $actor);
            $left = 0;
        }

        return $budget - $left;
    }

    private function postAllocation(Invoice $invoice, ?string $installmentId, int $cents, PaymentTransaction $tx, ?User $actor): void
    {
        Payment::query()->create([
            'university_id' => $invoice->university_id,
            'invoice_id' => $invoice->id,
            'payment_transaction_id' => $tx->id,
            'invoice_installment_id' => $installmentId,
            'amount' => Money::fromCents($cents),
            'allocation_type' => $tx->method === PaymentMethod::Deposit ? 'deposit_usage' : 'payment',
            'status' => Payment::POSTED,
            'paid_at' => $tx->paid_at?->toDateString() ?? now()->toDateString(),
            'effective_at' => $tx->paid_at ?? now(),
            'method' => $tx->method->value,
            'reference' => $tx->transaction_number,
            'channel' => $tx->provider,
            'recorded_by' => $actor?->id,
        ]);
        $invoice->paid_amount = Money::fromCents(Money::toCents($invoice->paid_amount) + $cents);
    }

    /** @param  array<int, string>  $touched */
    private function afterSettlement(PaymentTransaction $tx, array $touched, int $leftover): void
    {
        foreach ($touched as $invoiceId) {
            $invoice = Invoice::query()->find($invoiceId);

            if ($invoice !== null) {
                $this->penalties->onPrincipalPaid($invoice, CarbonImmutable::parse($tx->paid_at));
            }
        }

        if ($tx->method !== PaymentMethod::Deposit) {
            $this->notifier->student($tx->student, 'keu.payment_succeeded', ['amount' => Money::format($tx->amount), 'receipt' => (string) $tx->receipt_number]);
        }

        if ($leftover > 0 && $tx->method !== PaymentMethod::Deposit) {
            $this->notifier->student($tx->student, 'keu.deposit_credited', [
                'amount' => Money::format(Money::fromCents($leftover)),
                'reason' => $tx->target_type === PaymentTransaction::TARGET_DEPOSIT ? 'titipan' : 'kelebihan pembayaran',
            ]);
        }
    }

    /** Untuk rekonsiliasi/laporan: tagihan masih terbuka? */
    public static function isOpen(Invoice $invoice): bool
    {
        return in_array($invoice->status, InvoiceStatus::open(), true);
    }
}
