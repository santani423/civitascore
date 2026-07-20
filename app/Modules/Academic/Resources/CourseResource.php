<?php

namespace Modules\Academic\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Academic\Models\Course;

/**
 * @mixin Course
 */
class CourseResource extends JsonResource
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
            'curriculum_id' => $this->curriculum_id,
            'curriculum_name' => $this->whenLoaded('curriculum', fn () => $this->curriculum?->name),
            'code' => $this->code,
            'name' => $this->name,
            'credits' => $this->credits,
            'semester_level' => $this->semester_level,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
