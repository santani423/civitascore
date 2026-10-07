<?php

namespace Modules\HumanResource\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\HumanResource\Models\LeaveRequest;
use Modules\HumanResource\Resources\Concerns\PresentsHrRelations;

/**
 * @mixin LeaveRequest
 */
class LeaveRequestResource extends JsonResource
{
    use PresentsHrRelations;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'employee' => $this->employeeSummary(),
            'leave_type' => $this->leave_type->value,
            'leave_type_label' => $this->leave_type->label(),
            'start_date' => $this->start_date->toDateString(),
            'end_date' => $this->end_date->toDateString(),
            'days' => $this->days,
            'reason' => $this->reason,
            'attachment_file_id' => $this->attachment_file_id,
            'attachment_file' => $this->fileSummary('attachmentFile'),
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'hr_request_id' => $this->whenLoaded('hrRequest', fn () => $this->hrRequest?->id),
            'requested_by_name' => $this->whenLoaded('requester', fn () => $this->requester?->name),
            'submitted_at' => $this->submitted_at?->toIso8601String(),
            'approved_by_name' => $this->whenLoaded('approver', fn () => $this->approver?->name),
            'approved_at' => $this->approved_at?->toIso8601String(),
            'rejected_by_name' => $this->whenLoaded('rejecter', fn () => $this->rejecter?->name),
            'rejected_at' => $this->rejected_at?->toIso8601String(),
            'approval_note' => $this->approval_note,
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
