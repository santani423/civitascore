<?php

namespace Modules\Finance\Services;

use App\Models\User;
use App\Support\Http\Exceptions\ConflictException;
use Modules\Academic\Models\Student;
use Modules\AuditLog\Enums\AuditAction;
use Modules\AuditLog\Services\AuditLogService;
use Modules\Finance\Enums\AdjustmentType;
use Modules\Finance\Enums\AidAwardStatus;
use Modules\Finance\Enums\InvoiceLineType;
use Modules\Finance\Models\DiscountPolicy;
use Modules\Finance\Models\FinancialAidAward;
use Modules\Finance\Models\Invoice;
use Modules\Finance\Models\InvoiceAdjustment;
use Modules\Finance\Support\Approvals;
use Modules\Finance\Support\Money;

/**
 * Beasiswa, subsidi, potongan, KIP (F-05). Award berlaku pada tagihan yang
 * terbit sesudah disetujui; penerapan ke tagihan berjalan dilakukan lewat
 * penyesuaian turun yang juga perlu persetujuan (BR-KEU-24).
 */
class AidService
{
    public function __construct(
        private readonly AdjustmentService $adjustments,
        private readonly FinanceNotifier $notifier,
        private readonly AuditLogService $audit,
        private readonly AidCalculator $calculator,
    ) {}

    /** @param  array<string, mixed>  $overrides */
    public function request(Student $student, DiscountPolicy $policy, array $overrides, string $basis, User $actor, ?string $evidenceFileId = null): FinancialAidAward
    {
        if (! $policy->is_active) {
            throw new ConflictException('Jenis beasiswa/potongan ini tidak aktif.');
        }

        $open = FinancialAidAward::query()->where('student_id', $student->id)->where('discount_policy_id', $policy->id)
            ->whereIn('status', [AidAwardStatus::PendingApproval, AidAwardStatus::Active])->exists();

        if ($open) {
            throw new ConflictException('Mahasiswa ini sudah memiliki penetapan aktif/menunggu untuk jenis yang sama.');
        }

        return FinancialAidAward::query()->create([
            'student_id' => $student->id,
            'discount_policy_id' => $policy->id,
            'calc_type' => $overrides['calc_type'] ?? $policy->calc_type,
            'value' => $overrides['value'] ?? $policy->value,
            'max_amount' => $overrides['max_amount'] ?? $policy->max_amount,
            'fee_component_ids' => $overrides['fee_component_ids'] ?? null,
            'start_term_id' => $overrides['start_term_id'] ?? null,
            'end_term_id' => $overrides['end_term_id'] ?? null,
            'status' => AidAwardStatus::PendingApproval,
            'basis' => $basis,
            'evidence_file_id' => $evidenceFileId,
            'requested_by' => $actor->id,
        ]);
    }

    public function approve(FinancialAidAward $award, User $actor, ?string $note = null): FinancialAidAward
    {
        $this->assertPending($award);
        Approvals::assertDistinct($actor, [$award->requested_by]);
        $award->forceFill(['status' => AidAwardStatus::Active, 'decided_by' => $actor->id, 'decided_at' => now(), 'decision_note' => $note])->save();
        $this->audit->recordAction($award, AuditAction::Approved, ['policy' => $award->policy->code], $note);
        $this->notifier->student($award->student, 'keu.aid_decided', ['policy' => $award->policy->name, 'status' => 'disetujui dan berlaku untuk tagihan berikutnya']);

        return $award;
    }

    public function reject(FinancialAidAward $award, User $actor, string $note): FinancialAidAward
    {
        $this->assertPending($award);
        Approvals::assertDistinct($actor, [$award->requested_by]);
        $award->forceFill(['status' => AidAwardStatus::Rejected, 'decided_by' => $actor->id, 'decided_at' => now(), 'decision_note' => $note])->save();
        $this->notifier->student($award->student, 'keu.aid_decided', ['policy' => $award->policy->name, 'status' => 'ditolak: '.$note]);

        return $award;
    }

    public function revoke(FinancialAidAward $award, User $actor, string $reason): FinancialAidAward
    {
        if ($award->status !== AidAwardStatus::Active) {
            throw new ConflictException('Hanya penetapan aktif yang dapat dicabut.');
        }

        $award->forceFill(['status' => AidAwardStatus::Revoked, 'revoked_by' => $actor->id, 'revoked_at' => now(), 'revoke_reason' => $reason])->save();
        $this->audit->recordAction($award, AuditAction::Updated, ['status' => 'revoked'], $reason);
        $this->notifier->student($award->student, 'keu.aid_decided', ['policy' => $award->policy->name, 'status' => 'dicabut mulai tagihan berikutnya: '.$reason]);

        return $award;
    }

    /**
     * Terapkan award aktif ke tagihan yang sudah terbit (retroaktif):
     * membuat penyesuaian turun yang menunggu persetujuan pejabat lain.
     */
    public function applyToInvoice(FinancialAidAward $award, Invoice $invoice, User $actor): InvoiceAdjustment
    {
        if ($award->status !== AidAwardStatus::Active || $award->student_id !== $invoice->student_id) {
            throw new ConflictException('Penetapan tidak aktif atau bukan milik mahasiswa pada tagihan ini.');
        }

        $charges = [];

        foreach ($invoice->items()->where('line_type', InvoiceLineType::Charge)->with('component')->get() as $item) {
            if ($item->component !== null) {
                $charges[] = ['component' => $item->component, 'cents' => Money::toCents($item->amount)];
            }
        }

        $existingDiscount = $invoice->items()->where('source_type', 'financial_aid_award')->where('source_id', $award->id)->exists();

        if ($existingDiscount || $charges === []) {
            throw new ConflictException('Penetapan ini sudah diterapkan atau tagihan tidak memiliki komponen yang dapat dipotong.');
        }

        $cents = -array_sum(array_column($this->calculator->previewAward($award, $charges), 'amount_cents'));
        $cents = min($cents, Money::toCents($invoice->amount));

        if ($cents <= 0) {
            throw new ConflictException('Penetapan ini tidak menghasilkan potongan untuk tagihan tersebut.');
        }

        return $this->adjustments->request($invoice, AdjustmentType::Decrease, Money::fromCents($cents), 'AID_RETROACTIVE', "Penerapan {$award->policy->name} pada tagihan berjalan. {$award->basis}", $actor);
    }

    private function assertPending(FinancialAidAward $award): void
    {
        if ($award->status !== AidAwardStatus::PendingApproval) {
            throw new ConflictException('Penetapan ini sudah diputuskan.');
        }
    }
}
