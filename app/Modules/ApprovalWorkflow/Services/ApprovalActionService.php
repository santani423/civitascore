<?php

namespace Modules\ApprovalWorkflow\Services;

use App\Models\User;
use App\Support\Http\Exceptions\ConflictException;
use Illuminate\Support\Facades\DB;
use Modules\ApprovalWorkflow\Enums\ApprovalActionType;
use Modules\ApprovalWorkflow\Enums\ApprovalHistoryEvent;
use Modules\ApprovalWorkflow\Enums\ApprovalRejectAction;
use Modules\ApprovalWorkflow\Enums\ApprovalRequestStatus;
use Modules\ApprovalWorkflow\Enums\ApprovalRequestStepStatus;
use Modules\ApprovalWorkflow\Events\ApprovalRequestApproved;
use Modules\ApprovalWorkflow\Events\ApprovalRequestRejected;
use Modules\ApprovalWorkflow\Events\ApprovalRequestStepAdvanced;
use Modules\ApprovalWorkflow\Models\ApprovalHistory;
use Modules\ApprovalWorkflow\Models\ApprovalRequest;
use Modules\ApprovalWorkflow\Models\ApprovalRequestStep;
use Modules\ApprovalWorkflow\Events\ApprovalRequestResubmitted;
use Modules\ApprovalWorkflow\Events\ApprovalRequestReturned;
use Modules\ApprovalWorkflow\Notifications\ApprovalStepAssigned;
use Modules\ApprovalWorkflow\Support\ApproverResolver;
use Modules\SystemSetting\Services\SystemSettingService;

class ApprovalActionService
{
    private const DEFAULT_MAX_RESUBMISSIONS = 3;

    public function __construct(
        private readonly ApproverResolver $approverResolver,
        private readonly SystemSettingService $settings,
    ) {}

    public function approve(ApprovalRequestStep $step, User $actor, ?string $comment = null): ApprovalRequest
    {
        $this->guardActionable($step);

        return DB::transaction(function () use ($step, $actor, $comment): ApprovalRequest {
            $step->actions()->create([
                'acted_by' => $actor->id,
                'action' => ApprovalActionType::Approve,
                'comment' => $comment,
            ]);

            $step->update(['status' => ApprovalRequestStepStatus::Approved, 'acted_at' => now()]);

            $request = $step->request;
            $nextStep = $request->steps()->where('sequence', '>', $step->sequence)->orderBy('sequence')->first();

            if ($nextStep) {
                $nextStep->update(['status' => ApprovalRequestStepStatus::InReview]);
                $request->update(['current_step_id' => $nextStep->id]);

                ApprovalHistory::create([
                    'approval_request_id' => $request->id,
                    'approval_request_step_id' => $nextStep->id,
                    'event' => ApprovalHistoryEvent::StepAdvanced,
                    'actor_id' => $actor->id,
                    'description' => "Langkah '{$step->workflowStep->name}' disetujui, lanjut ke '{$nextStep->workflowStep->name}'.",
                ]);

                $request->refresh();

                ApprovalRequestStepAdvanced::dispatch($request, $nextStep);

                return $request;
            }

            $request->update([
                'status' => ApprovalRequestStatus::Approved,
                'current_step_id' => null,
                'completed_at' => now(),
            ]);

            ApprovalHistory::create([
                'approval_request_id' => $request->id,
                'approval_request_step_id' => $step->id,
                'event' => ApprovalHistoryEvent::Approved,
                'actor_id' => $actor->id,
                'description' => 'Pengajuan telah disetujui seluruhnya.',
            ]);

            $request->refresh();

            ApprovalRequestApproved::dispatch($request);

            return $request;
        });
    }

    public function reject(ApprovalRequestStep $step, User $actor, string $comment): ApprovalRequest
    {
        $this->guardActionable($step);

        return DB::transaction(function () use ($step, $actor, $comment): ApprovalRequest {
            $step->actions()->create([
                'acted_by' => $actor->id,
                'action' => ApprovalActionType::Reject,
                'comment' => $comment,
            ]);

            $step->update(['status' => ApprovalRequestStepStatus::Rejected, 'acted_at' => now()]);

            $request = $step->request;
            $rejectAction = $step->workflowStep->action_on_reject;

            if ($rejectAction === ApprovalRejectAction::ReturnToPreviousStep) {
                $previousStep = $request->steps()->where('sequence', '<', $step->sequence)->orderByDesc('sequence')->first();

                if ($previousStep) {
                    $previousStep->update(['status' => ApprovalRequestStepStatus::Pending, 'acted_at' => null]);
                    $step->update(['status' => ApprovalRequestStepStatus::Skipped]);
                    $request->update(['current_step_id' => $previousStep->id, 'status' => ApprovalRequestStatus::InProgress]);

                    ApprovalHistory::create([
                        'approval_request_id' => $request->id,
                        'approval_request_step_id' => $previousStep->id,
                        'event' => ApprovalHistoryEvent::ReturnedToPreviousStep,
                        'actor_id' => $actor->id,
                        'description' => "Ditolak pada langkah '{$step->workflowStep->name}', dikembalikan ke langkah sebelumnya.",
                    ]);

                    return $request->refresh();
                }

                // No previous step exists (rejected at the very first step)
                // — nowhere to return to, falls through to StopWorkflow.
            }

            $request->update([
                'status' => ApprovalRequestStatus::Rejected,
                'current_step_id' => null,
                'completed_at' => now(),
            ]);

            ApprovalHistory::create([
                'approval_request_id' => $request->id,
                'approval_request_step_id' => $step->id,
                'event' => ApprovalHistoryEvent::Rejected,
                'actor_id' => $actor->id,
                'description' => "Pengajuan ditolak pada langkah '{$step->workflowStep->name}'.",
            ]);

            $request->refresh();

            ApprovalRequestRejected::dispatch($request);

            return $request;
        });
    }

    public function delegate(ApprovalRequestStep $step, User $actor, User $delegateTo, ?string $comment = null): ApprovalRequest
    {
        $this->guardActionable($step);

        return DB::transaction(function () use ($step, $actor, $delegateTo, $comment): ApprovalRequest {
            $step->actions()->create([
                'acted_by' => $actor->id,
                'action' => ApprovalActionType::Delegate,
                'comment' => $comment,
                'delegated_to' => $delegateTo->id,
            ]);

            $step->update(['assigned_approver_user_id' => $delegateTo->id]);

            $request = $step->request;

            ApprovalHistory::create([
                'approval_request_id' => $request->id,
                'approval_request_step_id' => $step->id,
                'event' => ApprovalHistoryEvent::StepAdvanced,
                'actor_id' => $actor->id,
                'description' => "Langkah '{$step->workflowStep->name}' didelegasikan kepada {$delegateTo->name}.",
            ]);

            $delegateTo->notify(new ApprovalStepAssigned($step->refresh()));

            return $request;
        });
    }

    /**
     * Mengembalikan pengajuan ke pemohon untuk direvisi (berbeda dengan
     * ApprovalRejectAction::ReturnToPreviousStep yang mengembalikan ke
     * approver sebelumnya). Pengajuan berhenti di status `returned` sampai
     * pemohon mengajukan ulang lewat resubmit().
     */
    public function returnToRequester(ApprovalRequestStep $step, User $actor, string $comment): ApprovalRequest
    {
        $this->guardActionable($step);

        return DB::transaction(function () use ($step, $actor, $comment): ApprovalRequest {
            $step->actions()->create([
                'acted_by' => $actor->id,
                'action' => ApprovalActionType::Return,
                'comment' => $comment,
            ]);

            $step->update(['status' => ApprovalRequestStepStatus::Returned, 'acted_at' => now()]);

            $request = $step->request;
            $request->update([
                'status' => ApprovalRequestStatus::Returned,
                'current_step_id' => null,
                'returned_at' => now(),
            ]);

            ApprovalHistory::create([
                'approval_request_id' => $request->id,
                'approval_request_step_id' => $step->id,
                'event' => ApprovalHistoryEvent::ReturnedToRequester,
                'actor_id' => $actor->id,
                'description' => "Dikembalikan ke pemohon pada langkah '{$step->workflowStep->name}': {$comment}",
            ]);

            $request->refresh();

            ApprovalRequestReturned::dispatch($request, $comment);

            return $request;
        });
    }

    /**
     * Pengajuan yang dikembalikan diajukan ulang: seluruh langkah direset dan
     * alur dimulai lagi dari langkah pertama (persetujuan sebelumnya tidak
     * berlaku lagi karena isi pengajuan sudah berubah). Jumlah pengajuan
     * ulang dibatasi pengaturan `approval.max_resubmissions`.
     */
    public function resubmit(ApprovalRequest $request, User $actor): ApprovalRequest
    {
        if ($request->status !== ApprovalRequestStatus::Returned) {
            throw new ConflictException('Hanya pengajuan yang dikembalikan yang dapat diajukan ulang.');
        }

        $limit = (int) $this->settings->get('approval.max_resubmissions', self::DEFAULT_MAX_RESUBMISSIONS);

        if ($request->resubmission_count >= $limit) {
            throw new ConflictException("Pengajuan ini sudah diajukan ulang {$limit} kali, batas maksimum tercapai. Silakan buat pengajuan baru.");
        }

        return DB::transaction(function () use ($request, $actor): ApprovalRequest {
            $request->loadMissing('steps.workflowStep');
            $firstStep = null;

            foreach ($request->steps as $step) {
                $step->update([
                    'status' => ApprovalRequestStepStatus::Pending,
                    'acted_at' => null,
                    'assigned_approver_user_id' => $this->approverResolver->resolveAssignedUserId($step->workflowStep, $request),
                ]);
                $firstStep ??= $step;
            }

            $firstStep?->update(['status' => ApprovalRequestStepStatus::InReview]);

            $request->update([
                'status' => ApprovalRequestStatus::InProgress,
                'current_step_id' => $firstStep?->id,
                'resubmission_count' => $request->resubmission_count + 1,
                'returned_at' => null,
                'completed_at' => null,
            ]);

            ApprovalHistory::create([
                'approval_request_id' => $request->id,
                'approval_request_step_id' => $firstStep?->id,
                'event' => ApprovalHistoryEvent::Resubmitted,
                'actor_id' => $actor->id,
                'description' => 'Pengajuan direvisi dan diajukan ulang.',
            ]);

            $request->refresh();

            ApprovalRequestResubmitted::dispatch($request);

            if ($firstStep !== null) {
                ApprovalRequestStepAdvanced::dispatch($request, $firstStep->refresh());
            }

            return $request;
        });
    }

    private function guardActionable(ApprovalRequestStep $step): void
    {
        if ($step->status !== ApprovalRequestStepStatus::InReview) {
            throw new ConflictException('Langkah persetujuan ini sudah diproses atau belum menjadi giliran saat ini.');
        }
    }
}
