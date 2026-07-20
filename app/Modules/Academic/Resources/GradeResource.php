<?php

namespace Modules\Academic\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Academic\Models\Grade;

/**
 * @mixin Grade
 */
class GradeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'krs_item_id' => $this->krs_item_id,
            'student_id' => $this->whenLoaded('krsItem', fn () => $this->krsItem?->student_id),
            'student_name' => $this->whenLoaded('krsItem', fn () => $this->krsItem?->student?->name),
            'student_nim' => $this->whenLoaded('krsItem', fn () => $this->krsItem?->student?->nim),
            'course_name' => $this->whenLoaded('krsItem', fn () => $this->krsItem?->classSection?->course?->name),
            'course_code' => $this->whenLoaded('krsItem', fn () => $this->krsItem?->classSection?->course?->code),
            'academic_term_label' => $this->whenLoaded('krsItem', fn () => $this->krsItem?->academicTerm?->label()),
            'letter_grade' => $this->letter_grade?->value,
            'score' => $this->score,
            'submitted_at' => $this->submitted_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
