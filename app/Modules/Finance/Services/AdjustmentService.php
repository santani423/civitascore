<?php

namespace Modules\Finance\Services;

use App\Models\User;
use App\Support\Http\Exceptions\ConflictException;
use Illuminate\Support\Facades\DB;
use Modules\Academic\Models\Student;
use Modules\AuditLog\Enums\AuditAction;
use Modules\AuditLog\Services\AuditLogService;
use Modules\Finance\Enums\AdjustmentType;
use Modules\Finance\Enums\ApprovalStatus;
use Modules\Finance\Enums\BillGroup;
use Modules\Finance\Enums\DepositEntryType;
use Modules\Finance\Enums\InvoiceLineType;
use Modules\Finance\Enums\InvoiceStatus;
use Modules\Finance\Enums\TransactionStatus;
use Modules\Finance\Models\Invoice;
use Modules\Finance\Models\InvoiceAdjustment;
use Modules\Finance\Models\Payment;
use Modules\Finance\Models\PaymentTransaction;
use Modules\Finance\Support\Approvals;
use Modules\Finance\Support\FinanceNumbering;
use Modules\Finance\Support\Money;

/**
 * Perubahan tagihan yang sudah terbit hanya lewat penyesuaian beralasan
 * (KD-KEU-04, BR-KEU-08): naik, turun, pembebasan, pembebasan denda, void.
 * Pengaju ≠ penyetuju (SOD-KEU-06). Penyesuaian turun atas tagihan yang
 * sudah dibayar memindahkan kelebihan ke deposit (BR-KEU-46).
 */
class AdjustmentService
{
    public function __construct(
        private readonly InvoiceLedger $ledger,
        private readonly DepositService $deposits,
        private readonly FinanceNumbering $numbering,
        private readonly AuditLogService $audit,
        private readonly InvoiceWriter $writer,
    ) {}

    public function request(Invoice $invoice, AdjustmentType $type, string $amount, string $reasonCode, string $reasonNote, User $actor, ?string $evidenceFileId = null): InvoiceAdjustment
    {
        $cents = $type === AdjustmentType::Void ? Money::toCents($invoice->amount) : Money::toCents($amount);
        $this->validate($invoice, $type, $cents);

        if (InvoiceAdjustment::query()->where('invoice_id', $invoice->id)->where('status', ApprovalStatus::PendingApproval)->exists()) {
            throw new ConflictException('Masih ada penyesuaian lain yang menunggu persetujuan untuk tagihan ini.');
        }

        $adjustment = InvoiceAdjustment::query()->create([
            'adjustment_number' => $this->numbering->next('adjustment'),
            'invoice_id' => $invoice->id,
            'type' => $type,
            'amount' => Money::fromCents($this->signed($type, $cents)),
            'reason_code' => $reasonCode,
            'reason_note' => $reasonNote,
            'evidence_file_id' => $evidenceFileId,
            'status' => ApprovalStatus::PendingApproval,
            'requested_by' => $actor->id,
        ]);

        $this->audit->recordAction($invoice, AuditAction::Created, ['adjustment' => $adjustment->adjustment_number, 'type' => $type->value, 'amount' => (string) $adjustment->amount], $reasonNote);

        return $adjustment;
    }

    public function approve(InvoiceAdjustment $adjustment, User $actor, ?string $note = null): InvoiceAdjustment
    {
        if ($adjustment->status !== ApprovalStatus::PendingApproval) {
            throw new ConflictException('Penyesuaian ini sudah diputuskan.');
        }

        Approvals::assertDistinct($actor, [$adjustment->requested_by]);

        DB::transaction(function () use ($adjustment, $actor, $note): void {
            $adjustment->forceFill(['decided_by' => $actor->id, 'decided_at' => now(), 'decision_note' => $note])->save();
            $this->apply($adjustment, $actor);
        });

        $this->audit->recordAction($adjustment->invoice, AuditAction::Approved, ['adjustment' => $adjustment->adjustment_number], $note);

        return $adjustment->fresh();
    }

    public function reject(InvoiceAdjustment $adjustment, User $actor, string $note): InvoiceAdjustment
    {
        if ($adjustment->status !== ApprovalStatus::PendingApproval) {
            throw new ConflictException('Penyesuaian ini sudah diputuskan.');
        }

        Approvals::assertDistinct($actor, [$adjustment->requested_by]);
        $adjustment->forceFill(['status' => ApprovalStatus::Rejected, 'decided_by' => $actor->id, 'decided_at' => now(), 'decision_note' => $note])->save();
        $this->audit->recordAction($adjustment->invoice, AuditAction::Rejected, ['adjustment' => $adjustment->adjustment_number], $note);

        return $adjustment;
    }

    /**
     * Penyesuaian otomatis berbasis kebijakan (perubahan SKS, voucher) —
     * tanpa persetujuan manusia, tetapi tetap tercatat sebagai penyesuaian.
     */
    public function applySystem(Invoice $invoice, AdjustmentType $type, int $cents, string $reasonCode, string $note, string $itemSourceType, ?User $actor = null, ?InvoiceLineType $lineType = null): InvoiceAdjustment
    {
        return DB::transaction(function () use ($invoice, $type, $cents, $reasonCode, $note, $itemSourceType, $actor, $lineType): InvoiceAdjustment {
            $adjustment = InvoiceAdjustment::query()->create([
                'university_id' => $invoice->university_id,
                'adjustment_number' => $this->numbering->next('adjustment', null, $invoice->university_id),
                'invoice_id' => $invoice->id,
                'type' => $type,
                'amount' => Money::fromCents($this->signed($type, $cents)),
                'reason_code' => $reasonCode,
                'reason_note' => $note,
                'status' => ApprovalStatus::PendingApproval,
                'requested_by' => $actor?->id,
            ]);

            $this->apply($adjustment, $actor, $itemSourceType, $lineType);

            return $adjustment->fresh();
        });
    }

    private function apply(InvoiceAdjustment $adjustment, ?User $actor, string $itemSourceType = 'invoice_adjustment', ?InvoiceLineType $lineType = null): void
    {
        $invoice = $this->ledger->lock($adjustment->invoice_id);
        $cents = Money::toCents($adjustment->amount);
        $this->validate($invoice, $adjustment->type, abs($cents));

        if ($adjustment->type === AdjustmentType::Void) {
            $invoice->forceFill(['status' => InvoiceStatus::Cancelled, 'cancelled_at' => now(), 'cancel_reason' => $adjustment->reason_note])->save();
            $this->ledger->recalculate($invoice);
        } else {
            $lineType ??= match ($adjustment->type) {
                AdjustmentType::Waiver, AdjustmentType::PenaltyWaiver => InvoiceLineType::Waiver,
                AdjustmentType::Voucher => InvoiceLineType::Voucher,
                default => InvoiceLineType::Adjustment,
            };

            $this->writer->addLine($invoice, [
                'line_type' => $lineType,
                'description' => $adjustment->type->label().': '.$adjustment->reason_note,
                'amount_cents' => $cents,
                'source_type' => $itemSourceType,
                'source_id' => $adjustment->id,
                'sort_order' => 900,
            ]);

            $this->ledger->recalculate($invoice);
            $this->ledger->rebalanceInstallments($invoice);
            $this->moveExcessToDeposit($invoice, $actor);
            $this->ledger->recalculate($invoice);
        }

        $adjustment->forceFill(['status' => ApprovalStatus::Applied, 'applied_at' => now()])->save();
    }

    /** Kelebihan bayar setelah penyesuaian turun → deposit (alokasi negatif `credit_out`). */
    private function moveExcessToDeposit(Invoice $invoice, ?User $actor): void
    {
        $excess = Money::toCents($invoice->paid_amount) - max(0, Money::toCents($invoice->amount));

        if ($excess <= 0) {
            return;
        }

        Payment::query()->create([
            'university_id' => $invoice->university_id,
            'invoice_id' => $invoice->id,
            'amount' => Money::fromCents(-$excess),
            'allocation_type' => 'credit_out',
            'status' => Payment::POSTED,
            'paid_at' => now()->toDateString(),
            'effective_at' => now(),
            'method' => 'deposit',
            'reference' => $invoice->invoice_number,
            'channel' => 'deposit',
            'recorded_by' => $actor?->id,
        ]);

        $this->deposits->credit(Student::query()->findOrFail($invoice->student_id), $excess, DepositEntryType::AdjustmentCredit, 'invoice', $invoice->id, 'Kelebihan bayar '.$invoice->invoice_number, $actor?->id);
    }

    private function validate(Invoice $invoice, AdjustmentType $type, int $cents): void
    {
        if (in_array($invoice->status, [InvoiceStatus::Cancelled, InvoiceStatus::Waived, InvoiceStatus::Draft], true)) {
            throw new ConflictException('Tagihan berstatus '.$invoice->status->label().' tidak dapat disesuaikan.');
        }

        if ($type !== AdjustmentType::Void && $cents <= 0) {
            throw new ConflictException('Nominal penyesuaian harus lebih dari nol.');
        }

        if ($type === AdjustmentType::Void) {
            $pending = PaymentTransaction::query()->whereIn('target_id', [$invoice->id, ...$invoice->installments()->pluck('id')->all()])
                ->whereIn('status', [TransactionStatus::Pending, TransactionStatus::AwaitingVerification])->exists();

            if (Money::toCents($invoice->paid_amount) > 0 || $pending) {
                throw new ConflictException('Tagihan yang sudah menerima pembayaran atau memiliki transaksi berjalan tidak dapat dibatalkan; gunakan penyesuaian turun (BR-KEU-12).', 'INVOICE_HAS_PAYMENTS');
            }
        }

        if ($type === AdjustmentType::PenaltyWaiver && $invoice->bill_group !== BillGroup::Penalty) {
            throw new ConflictException('Pembebasan denda hanya untuk tagihan denda.');
        }

        if (in_array($type, [AdjustmentType::Decrease, AdjustmentType::Waiver, AdjustmentType::PenaltyWaiver, AdjustmentType::Voucher], true) && $cents > Money::toCents($invoice->amount)) {
            throw new ConflictException('Pengurangan melebihi nilai tagihan saat ini.');
        }
    }

    private function signed(AdjustmentType $type, int $cents): int
    {
        return $type === AdjustmentType::Increase ? $cents : -$cents;
    }
}
