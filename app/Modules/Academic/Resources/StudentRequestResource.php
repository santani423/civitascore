<?php

namespace Modules\Academic\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Academic\Enums\StudentRequestStatus;
use Modules\Academic\Enums\StudentRequestType;
use Modules\Academic\Models\StudentRequest;
use Modules\Academic\Services\StudentRequestService;
use Modules\ApprovalWorkflow\Models\ApprovalHistory;

/**
 * @mixin StudentRequest
 */
class StudentRequestResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $approval = $this->relationLoaded('approvalRequest') ? $this->approvalRequest : null;
        $currentStep = $approval?->relationLoaded('currentStep') ? $approval->currentStep : null;

        return [
            'id' => $this->id,
            'type' => $this->type->value,
            'type_label' => $this->type->label(),
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'title' => $this->title,
            'description' => $this->description,
            'payload' => $this->payload,
            'letter_type_label' => $this->type === StudentRequestType::Letter
                ? (StudentRequestService::LETTER_TYPES[$this->payload['letter_type'] ?? ''] ?? null)
                : null,
            'attachment' => $this->relationLoaded('attachment') && $this->attachment ? [
                'id' => $this->attachment->id,
                'name' => $this->attachment->original_name,
                'size_bytes' => $this->attachment->size_bytes,
            ] : null,
            'term' => $this->whenLoaded('academicTerm', fn () => $this->academicTerm ? [
                'id' => $this->academicTerm->id,
                'label' => $this->academicTerm->label(),
            ] : null),
            'student' => $this->whenLoaded('student', fn () => [
                'id' => $this->student->id,
                'nim' => $this->student->nim,
                'name' => $this->student->name,
                'status' => $this->student->status->value,
                'status_label' => $this->student->status->label(),
            ]),
            'submitted_at' => $this->submitted_at?->toIso8601String(),
            'decided_at' => $this->decided_at?->toIso8601String(),
            'reviewer' => $this->whenLoaded('decider', fn () => $this->decider?->name),
            'decision_note' => $this->decision_note,
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            'current_step' => $currentStep ? [
                'name' => $currentStep->relationLoaded('workflowStep') ? $currentStep->workflowStep?->name : null,
                'status' => $currentStep->status->value,
            ] : null,
            'can_act' => $currentStep !== null && $this->status === StudentRequestStatus::Submitted
                ? ($request->user()?->can('act', $currentStep) ?? false)
                : false,
            'history' => $approval?->relationLoaded('histories')
                ? $approval->histories->map(fn (ApprovalHistory $history): array => [
                    'event' => $history->event->value,
                    'description' => $history->description,
                    'created_at' => $history->created_at?->toIso8601String(),
                ])->values()->all()
                : [],
            'has_letter' => $this->type === StudentRequestType::Letter && $this->status === StudentRequestStatus::Approved,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
