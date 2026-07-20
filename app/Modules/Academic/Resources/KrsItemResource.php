<?php

namespace Modules\Academic\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Academic\Models\KrsItem;

/**
 * @mixin KrsItem
 */
class KrsItemResource extends JsonResource
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
            'class_section_id' => $this->class_section_id,
            'course_name' => $this->whenLoaded('classSection', fn () => $this->classSection?->course?->name),
            'course_code' => $this->whenLoaded('classSection', fn () => $this->classSection?->course?->code),
            'class_code' => $this->whenLoaded('classSection', fn () => $this->classSection?->class_code),
            'academic_term_id' => $this->academic_term_id,
            'academic_term_label' => $this->whenLoaded('academicTerm', fn () => $this->academicTerm?->label()),
            'status' => $this->status->value,
            'letter_grade' => $this->whenLoaded('grade', fn () => $this->grade?->letter_grade?->value),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
