<?php

namespace Modules\Admission\Services\Assessment;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Modules\Admission\Enums\AssessmentItemStatus;
use Modules\Admission\Enums\AssessmentVersionStatus;
use Modules\Admission\Exceptions\DomainRuleException;
use Modules\Admission\Models\AssessmentItem;
use Modules\Admission\Models\AssessmentVersion;
use Modules\Admission\Services\Approval\PmbApprovalService;
use Modules\Admission\Support\HistoryRecorder;
use Modules\Admission\Support\StateTransition;
use Modules\ApprovalWorkflow\Services\ApprovalActionService;

/**
 * Persetujuan versi instrumen lewat approval engine yang sudah ada (docs/one-atma-talent/05 §3).
 * Keputusan memakai policy `act` pada langkah yang sama dengan halaman Persetujuan umum,
 * sehingga pemisahan tugas dan giliran approver berlaku di kedua jalur.
 */
final class AssessmentApprovalService
{
    public const DECISIONS = ['approve', 'reject', 'return'];

    public function __construct(
        private readonly PmbApprovalService $approvals,
        private readonly ApprovalActionService $actions,
        private readonly HistoryRecorder $history,
    ) {}

    /** Ajukan versi draft untuk ditelaah. Status versi berubah menjadi `in_review`. */
    public function submit(AssessmentVersion $version, User $requester, ?string $note = null): AssessmentVersion
    {
        return DB::transaction(function () use ($version, $requester, $note): AssessmentVersion {
            $version = AssessmentVersion::query()->whereKey($version->getKey())->lockForUpdate()->firstOrFail();
            StateTransition::assert($version->status, AssessmentVersionStatus::InReview, 'assessment_version');
            app(AssessmentConfigurationService::class)->assertReadyForReview($version);

            $request = $this->approvals->submit($version, PmbApprovalService::ASSESSMENT_VERSION, $requester, $note);

            $old = $version->status->value;
            $version->forceFill([
                'status' => AssessmentVersionStatus::InReview->value,
                'approval_request_id' => $request->getKey(),
                'submitted_at' => now(),
                'submitted_by' => $requester->getKey(),
                'change_note' => $note ?? $version->change_note,
            ])->save();

            $this->history->change($version, ['status' => $old], ['status' => AssessmentVersionStatus::InReview->value], $note);

            return $version->refresh();
        });
    }

    /**
     * Keputusan approver pada langkah yang sedang aktif. Pemisahan tugas dan giliran diperiksa
     * oleh policy dan engine; status versi disinkronkan lewat SyncApprovalDecisions.
     */
    public function decide(AssessmentVersion $version, User $actor, string $decision, ?string $comment = null): AssessmentVersion
    {
        if (! in_array($decision, self::DECISIONS, true)) {
            throw new DomainRuleException('Keputusan tidak dikenal.', 'INVALID_DECISION', 422);
        }

        if ($version->status !== AssessmentVersionStatus::InReview || $version->approval_request_id === null) {
            throw new DomainRuleException('Versi ini tidak sedang ditelaah.', 'NOT_IN_REVIEW', 409);
        }

        $request = $version->approvalRequest()->firstOrFail();
        $step = $request->currentStep ?? throw new DomainRuleException('Tidak ada langkah persetujuan yang aktif.', 'NO_ACTIVE_STEP', 409);

        Gate::forUser($actor)->authorize('act', $step);

        match ($decision) {
            'approve' => $this->actions->approve($step, $actor, $comment),
            'reject' => $this->actions->reject($step, $actor, (string) $comment),
            'return' => $this->actions->returnToRequester($step, $actor, (string) $comment),
        };

        return $version->refresh();
    }

    /**
     * Dipanggil SyncApprovalDecisions saat approval engine menutup pengajuan.
     * Hanya versi yang masih `in_review` yang berpindah; pemanggilan ganda tidak berefek.
     */
    public function onOutcome(AssessmentVersion $version, string $outcome): void
    {
        DB::transaction(function () use ($version, $outcome): void {
            $version = AssessmentVersion::query()->whereKey($version->getKey())->lockForUpdate()->firstOrFail();

            if ($version->status !== AssessmentVersionStatus::InReview) {
                return;
            }

            $target = match ($outcome) {
                'approved' => AssessmentVersionStatus::Approved,
                'rejected' => AssessmentVersionStatus::Rejected,
                'returned' => AssessmentVersionStatus::Draft,
                default => null,
            };

            if ($target === null) {
                return;
            }

            StateTransition::assert($version->status, $target, 'assessment_version');

            $old = $version->status->value;
            $version->forceFill(['status' => $target->value, 'approved_at' => $target === AssessmentVersionStatus::Approved ? now() : null])->save();

            if ($target === AssessmentVersionStatus::Approved) {
                // Butir ikut disetujui bersama versinya; yang sudah terbit tidak akan berubah lagi.
                AssessmentItem::query()->where('version_id', $version->getKey())->update(['status' => AssessmentItemStatus::Approved->value]);
            }

            $this->history->change($version, ['status' => $old], ['status' => $target->value], $outcome);
        });
    }
}
