<?php

namespace Modules\Internship\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Internship\Models\Internship;

/**
 * @mixin Internship
 */
class InternshipResource extends JsonResource
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
            'program_type' => $this->program_type->value,
            'institution_name' => $this->institution_name,
            'position' => $this->position,
            'supervisor_lecturer_id' => $this->supervisor_lecturer_id,
            'supervisor_name' => $this->whenLoaded('supervisor', fn () => $this->supervisor?->name),
            'start_date' => $this->start_date->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'status' => $this->status->value,
            'sks_converted' => $this->sks_converted,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
