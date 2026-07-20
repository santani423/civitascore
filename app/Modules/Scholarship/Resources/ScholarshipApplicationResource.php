<?php

namespace Modules\Scholarship\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Scholarship\Models\ScholarshipApplication;

/**
 * @mixin ScholarshipApplication
 */
class ScholarshipApplicationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'scholarship_id' => $this->scholarship_id,
            'scholarship_name' => $this->whenLoaded('scholarship', fn () => $this->scholarship?->name),
            'student_id' => $this->student_id,
            'student_name' => $this->whenLoaded('student', fn () => $this->student?->name),
            'student_nim' => $this->whenLoaded('student', fn () => $this->student?->nim),
            'status' => $this->status->value,
            'submitted_at' => $this->submitted_at->toIso8601String(),
            'reviewed_at' => $this->reviewed_at?->toIso8601String(),
            'notes' => $this->notes,
        ];
    }
}
