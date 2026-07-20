<?php

namespace Modules\Academic\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Academic\Models\ClassSection;

/**
 * @mixin ClassSection
 */
class ClassSectionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'study_program_id' => $this->study_program_id,
            'study_program_name' => $this->whenLoaded('studyProgram', fn () => $this->studyProgram?->name),
            'academic_term_id' => $this->academic_term_id,
            'academic_term_label' => $this->whenLoaded('academicTerm', fn () => $this->academicTerm?->label()),
            'course_id' => $this->course_id,
            'course_name' => $this->whenLoaded('course', fn () => $this->course?->name),
            'course_code' => $this->whenLoaded('course', fn () => $this->course?->code),
            'credits' => $this->whenLoaded('course', fn () => $this->course?->credits),
            'class_code' => $this->class_code,
            'capacity' => $this->capacity,
            'enrolled_count' => $this->whenCounted('krsItems'),
            'is_active' => $this->is_active,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
