<?php

namespace Modules\Academic\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Academic\Models\Student;

/**
 * @mixin Student
 */
class StudentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'study_program_id' => $this->study_program_id,
            'study_program_name' => $this->whenLoaded('studyProgram', fn () => $this->studyProgram->name),
            'nim' => $this->nim,
            'name' => $this->name,
            'email' => $this->email,
            'admission_year' => $this->admission_year,
            'status' => $this->status->value,
            'enrolled_at' => $this->enrolled_at->toDateString(),
            'graduated_at' => $this->graduated_at?->toDateString(),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
