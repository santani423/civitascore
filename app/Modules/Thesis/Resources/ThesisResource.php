<?php

namespace Modules\Thesis\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Thesis\Models\Thesis;

/**
 * @mixin Thesis
 */
class ThesisResource extends JsonResource
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
            'supervisor_lecturer_id' => $this->supervisor_lecturer_id,
            'supervisor_name' => $this->whenLoaded('supervisor', fn () => $this->supervisor?->name),
            'title' => $this->title,
            'thesis_type' => $this->thesis_type->value,
            'status' => $this->status->value,
            'submitted_at' => $this->submitted_at->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
