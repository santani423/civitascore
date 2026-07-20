<?php

namespace Modules\Academic\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Academic\Models\Curriculum;

/**
 * @mixin Curriculum
 */
class CurriculumResource extends JsonResource
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
            'name' => $this->name,
            'academic_year' => $this->academic_year,
            'is_active' => $this->is_active,
            'courses_count' => $this->whenCounted('courses'),
            'courses' => $this->whenLoaded('courses', fn () => CourseResource::collection($this->courses)),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
