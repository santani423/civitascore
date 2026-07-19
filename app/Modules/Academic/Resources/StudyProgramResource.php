<?php

namespace Modules\Academic\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Academic\Models\StudyProgram;

/**
 * @mixin StudyProgram
 */
class StudyProgramResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'faculty_id' => $this->faculty_id,
            'faculty_name' => $this->whenLoaded('faculty', fn () => $this->faculty?->name),
            'code' => $this->code,
            'name' => $this->name,
            'degree_level' => $this->degree_level,
            'is_active' => $this->is_active,
            // ->withCount() on the controller's query, not a relation load
            // — falls back to null (not queried) when the caller didn't
            // request it, same whenCounted() convention Laravel provides.
            'students_count' => $this->whenCounted('students'),
            'class_sections_count' => $this->whenCounted('classSections'),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
