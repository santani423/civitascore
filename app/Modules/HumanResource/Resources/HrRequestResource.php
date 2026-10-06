<?php

namespace Modules\HumanResource\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\ApprovalWorkflow\Models\ApprovalHistory;
use Modules\HumanResource\Models\HrRequest;
use Modules\HumanResource\Resources\Concerns\PresentsHrRelations;
use Modules\HumanResource\Services\HrRequestService;

/**
 * @mixin HrRequest
 */
class HrRequestResource extends JsonResource
{
    use PresentsHrRelations;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $request->user();

        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'employee' => $this->employeeSummary(),
            'type' => $this->type->value,
            'type_label' => $this->type->label(),
            'title' => $this->title,
            'description' => $this->description,
            'payload' => $this->payload,
            'leave_request_id' => $this->leave_request_id,
            'leave_request' => $this->whenLoaded('leaveRequest', fn () => $this->leaveRequest === null ? null : new LeaveRequestResource($this->leaveRequest)),
            'attachment_file_id' => $this->attachment_file_id,
            'attachment_file' => $this->fileSummary('attachmentFile'),
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'requested_by_name' => $this->whenLoaded('requester', fn () => $this->requester?->name),
            'approved_by_name' => $this->whenLoaded('approver', fn () => $this->approver?->name),
            'approved_at' => $this->approved_at?->toIso8601String(),
            'rejected_by_name' => $this->whenLoaded('rejecter', fn () => $this->rejecter?->name),
            'rejected_at' => $this->rejected_at?->toIso8601String(),
            'approval_note' => $this->approval_note,
            'requires_processing' => $this->requiresProcessing(),
            'processed_at' => $this->processed_at?->toIso8601String(),
            'processed_by_name' => $this->whenLoaded('processor', fn () => $this->processor?->name),
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            // Apakah user yang sedang login berhak memutuskan langkah yang
            // sedang berjalan — dipakai frontend untuk menampilkan tombol
            // Setujui/Tolak, keputusan tetap dicek ulang di backend.
            'can_act' => $user !== null && $this->relationLoaded('approvalRequest')
                ? app(HrRequestService::class)->canAct($this->resource, $user)
                : false,
            'current_step_name' => $this->whenLoaded('approvalRequest', fn () => $this->approvalRequest?->currentStep?->workflowStep->name),
            'histories' => $this->whenLoaded('approvalRequest', fn () => $this->approvalRequest?->relationLoaded('histories')
                ? $this->approvalRequest->histories->map(fn (ApprovalHistory $history): array => [
                    'id' => $history->id,
                    'event' => $history->event->value,
                    'description' => $history->description,
                    'actor_name' => $history->actor?->name,
                    'created_at' => $history->created_at->toIso8601String(),
                ])->all()
                : []),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
