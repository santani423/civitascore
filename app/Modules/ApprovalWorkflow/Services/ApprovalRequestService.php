<?php

namespace Modules\ApprovalWorkflow\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Modules\ApprovalWorkflow\Enums\ApprovalHistoryEvent;
use Modules\ApprovalWorkflow\Enums\ApprovalRequestStatus;
use Modules\ApprovalWorkflow\Enums\ApprovalRequestStepStatus;
use Modules\ApprovalWorkflow\Events\ApprovalRequestSubmitted;
use Modules\ApprovalWorkflow\Models\ApprovalHistory;
use Modules\ApprovalWorkflow\Models\ApprovalRequest;
use Modules\ApprovalWorkflow\Models\ApprovalWorkflow;
use Modules\ApprovalWorkflow\Support\ApproverResolver;

class ApprovalRequestService
{
    public function __construct(private readonly ApproverResolver $approverResolver) {}

    public function submit(Model $requestable, ApprovalWorkflow $workflow, User $requester, ?string $notes = null): ApprovalRequest
    {
        return DB::transaction(function () use ($requestable, $workflow, $requester, $notes): ApprovalRequest {
            $request = ApprovalRequest::create([
                'approval_workflow_id' => $workflow->id,
                'requestable_type' => $requestable->getMorphClass(),
                'requestable_id' => $requestable->getKey(),
                'requested_by' => $requester->id,
                'status' => ApprovalRequestStatus::InProgress,
                'submitted_at' => now(),
                'notes' => $notes,
            ]);

            $firstStep = null;

            foreach ($workflow->steps as $templateStep) {
                $requestStep = $request->steps()->create([
                    'approval_workflow_step_id' => $templateStep->id,
                    'sequence' => $templateStep->sequence,
                    'assigned_approver_user_id' => $this->approverResolver->resolveAssignedUserId($templateStep),
                    'status' => ApprovalRequestStepStatus::Pending,
                ]);

                $firstStep ??= $requestStep;
            }

            if ($firstStep) {
                $firstStep->update(['status' => ApprovalRequestStepStatus::InReview]);
                $request->update(['current_step_id' => $firstStep->id]);
            }

            ApprovalHistory::create([
                'approval_request_id' => $request->id,
                'approval_request_step_id' => $firstStep?->id,
                'event' => ApprovalHistoryEvent::Submitted,
                'actor_id' => $requester->id,
                'description' => 'Pengajuan disampaikan.',
            ]);

            $request->refresh();

            ApprovalRequestSubmitted::dispatch($request);

            return $request;
        });
    }
}
