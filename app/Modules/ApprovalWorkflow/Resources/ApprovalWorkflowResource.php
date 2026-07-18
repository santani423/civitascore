<?php

namespace Modules\ApprovalWorkflow\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\ApprovalWorkflow\Models\ApprovalWorkflow;

/**
 * @mixin ApprovalWorkflow
 */
class ApprovalWorkflowResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'workflowable_type' => $this->workflowable_type,
            'conditions' => $this->conditions,
            'is_active' => $this->is_active,
            'description' => $this->description,
            'steps' => $this->whenLoaded('steps', fn () => $this->steps->map(fn ($step) => [
                'id' => $step->id,
                'sequence' => $step->sequence,
                'name' => $step->name,
                'approver_type' => $step->approver_type->value,
                'approver_role_id' => $step->approver_role_id,
                'approver_user_id' => $step->approver_user_id,
                'action_on_reject' => $step->action_on_reject->value,
            ])),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
