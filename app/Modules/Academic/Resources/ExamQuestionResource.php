<?php

namespace Modules\Academic\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Academic\Models\ExamQuestion;

/**
 * Tampilan admin/dosen — menyertakan `is_correct` per opsi. Jangan pernah
 * dipakai untuk merender soal ke peserta ujian (lihat ExamAttemptResource).
 *
 * @mixin ExamQuestion
 */
class ExamQuestionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'exam_id' => $this->exam_id,
            'question_bank_item_id' => $this->question_bank_item_id,
            'question_text' => $this->question_text,
            'points' => $this->points,
            'order_index' => $this->order_index,
            'is_selected' => $this->is_selected,
            'options' => $this->whenLoaded('options', fn () => $this->options->map(fn ($option) => [
                'id' => $option->id,
                'option_text' => $option->option_text,
                'is_correct' => $option->is_correct,
                'order_index' => $option->order_index,
            ])),
        ];
    }
}
