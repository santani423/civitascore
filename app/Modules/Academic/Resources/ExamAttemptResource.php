<?php

namespace Modules\Academic\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;
use Modules\Academic\Models\ExamAttempt;
use Modules\Academic\Models\ExamQuestion;

/**
 * Tampilan aman-untuk-peserta — soal & opsi jawaban ditampilkan sesuai
 * urutan yang tersimpan di attempt (question_order/option_order), dan
 * `is_correct` TIDAK PERNAH disertakan (bandingkan ExamQuestionResource).
 *
 * @mixin ExamAttempt
 */
class ExamAttemptResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Collection<string, ExamQuestion> $questionsById */
        $questionsById = ExamQuestion::query()
            ->whereIn('id', $this->question_order)
            ->with('options')
            ->get()
            ->keyBy('id');

        $answersByQuestionId = $this->answers->keyBy('exam_question_id');

        return [
            'id' => $this->id,
            'exam_id' => $this->exam_id,
            'krs_item_id' => $this->krs_item_id,
            'attempt_number' => $this->attempt_number,
            'status' => $this->status->value,
            'started_at' => $this->started_at->toIso8601String(),
            'submitted_at' => $this->submitted_at?->toIso8601String(),
            'score' => $this->score,
            'questions' => collect($this->question_order)->map(function (string $questionId) use ($questionsById, $answersByQuestionId) {
                $question = $questionsById->get($questionId);
                $optionsById = $question?->options->keyBy('id');
                $orderedOptionIds = $this->option_order[$questionId] ?? [];

                return [
                    'id' => $questionId,
                    'question_text' => $question?->question_text,
                    'points' => $question?->points,
                    'options' => collect($orderedOptionIds)->map(fn (string $optionId) => [
                        'id' => $optionId,
                        'option_text' => $optionsById?->get($optionId)?->option_text,
                    ])->values()->all(),
                    'selected_option_id' => $answersByQuestionId->get($questionId)?->exam_question_option_id,
                ];
            })->values()->all(),
        ];
    }
}
