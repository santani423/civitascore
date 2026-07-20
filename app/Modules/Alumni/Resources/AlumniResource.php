<?php

namespace Modules\Alumni\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Alumni\Models\Alumni;

/**
 * @mixin Alumni
 */
class AlumniResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'student_id' => $this->student_id,
            'student_name' => $this->whenLoaded('student', fn () => $this->student?->name),
            'student_nim' => $this->whenLoaded('student', fn () => $this->student?->nim),
            'study_program_name' => $this->whenLoaded('student', fn () => $this->student?->studyProgram?->name),
            'graduation_year' => $this->graduation_year,
            'employment_status' => $this->employment_status->value,
            'company_name' => $this->company_name,
            'job_title' => $this->job_title,
            'waiting_period_months' => $this->waiting_period_months,
            'is_verified' => $this->is_verified,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
