<?php

namespace Modules\Academic\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Academic\Models\QuestionBankItem;

/**
 * @mixin QuestionBankItem
 */
class QuestionBankItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'course_id' => $this->course_id,
            'course_name' => $this->whenLoaded('course', fn () => $this->course?->name),
            'question_text' => $this->question_text,
            'points' => $this->points,
            'options' => $this->whenLoaded('options', fn () => $this->options->map(fn ($option) => [
                'id' => $option->id,
                'option_text' => $option->option_text,
                'is_correct' => $option->is_correct,
                'order_index' => $option->order_index,
            ])),
            'usage_count' => $this->whenCounted('examQuestions'),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
