<?php

namespace Modules\ApprovalWorkflow\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\ApprovalWorkflow\Models\ApprovalRequest;

/**
 * @mixin ApprovalRequest
 */
class ApprovalRequestResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'university_id' => $this->university_id,
            'approval_workflow_id' => $this->approval_workflow_id,
            'requestable_type' => $this->requestable_type,
            'requestable_id' => $this->requestable_id,
            'requested_by' => $this->requested_by,
            'current_step_id' => $this->current_step_id,
            'status' => $this->status->value,
            'submitted_at' => $this->submitted_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'notes' => $this->notes,
            'histories' => ApprovalHistoryResource::collection($this->whenLoaded('histories')),
            // Reuses ApprovalRequestStepPolicy::act() directly rather than
            // re-implementing the User-type/Role-type approver logic in the
            // frontend — see ApprovalRequestDetailPage.tsx. relationLoaded()
            // (not whenLoaded()) so this never triggers a lazy per-row query
            // on index(); false there is correct, the field isn't used on
            // that page.
            'can_act' => $this->relationLoaded('currentStep') && $this->currentStep
                ? ($request->user()?->can('act', $this->currentStep) ?? false)
                : false,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
