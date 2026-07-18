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
use Modules\ApprovalWorkflow\Notifications\ApprovalStepAssigned;

class ApprovalActionService
{
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

    private function guardActionable(ApprovalRequestStep $step): void
    {
        if ($step->status !== ApprovalRequestStepStatus::InReview) {
            throw new ConflictException('Langkah persetujuan ini sudah diproses atau belum menjadi giliran saat ini.');
        }
    }
}
