<?php

namespace Modules\Academic\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Academic\Models\Exam;

/**
 * @mixin Exam
 */
class ExamResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'class_section_id' => $this->class_section_id,
            'class_code' => $this->whenLoaded('classSection', fn () => $this->classSection?->class_code),
            'course_name' => $this->whenLoaded('classSection', fn () => $this->classSection?->course?->name),
            'title' => $this->title,
            'duration_minutes' => $this->duration_minutes,
            'starts_at' => $this->starts_at?->toIso8601String(),
            'ends_at' => $this->ends_at?->toIso8601String(),
            'creator_name' => $this->whenLoaded('creator', fn () => $this->creator?->name),
            'question_pool_size' => $this->whenCounted('questions'),
            'questions_per_participant' => $this->questions_per_participant,
            'question_selection_mode' => $this->question_selection_mode->value,
            'randomize_questions' => $this->randomize_questions,
            'randomize_options' => $this->randomize_options,
            'allow_back_navigation' => $this->allow_back_navigation,
            'show_result_after_submission' => $this->show_result_after_submission,
            'max_attempts' => $this->max_attempts,
            'is_published' => $this->is_published,
            'published_at' => $this->published_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
