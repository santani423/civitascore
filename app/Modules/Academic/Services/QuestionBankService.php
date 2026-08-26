<?php

namespace Modules\Academic\Services;

use App\Support\Http\Exceptions\ConflictException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Academic\Models\Exam;
use Modules\Academic\Models\ExamQuestion;
use Modules\Academic\Models\QuestionBankItem;

/**
 * Bank soal (spec §7) — kumpulan soal pilihan ganda yang dapat dipakai ulang
 * lintas ujian/semester, terlepas dari satu ujian tertentu. Menerapkan item
 * bank ke sebuah ujian membuat SALINAN (snapshot) sebagai ExamQuestion baru;
 * mengubah item bank setelahnya tidak memengaruhi ujian yang sudah
 * menerapkannya — konsisten dengan filosofi snapshot yang sama dipakai
 * ExamService::startAttempt() untuk stabilitas percobaan peserta.
 */
class QuestionBankService
{
    /**
     * @param  array<string, mixed>  $data  Divalidasi StoreQuestionBankItemRequest — question_text, points, options[], course_id opsional.
     */
    public function createItem(array $data): QuestionBankItem
    {
        return DB::transaction(function () use ($data): QuestionBankItem {
            $item = QuestionBankItem::query()->create([
                'course_id' => $data['course_id'] ?? null,
                'question_text' => $data['question_text'],
                'points' => $data['points'] ?? 1,
            ]);

            foreach ((array) $data['options'] as $index => $option) {
                $item->options()->create([
                    'option_text' => $option['option_text'],
                    'is_correct' => (bool) $option['is_correct'],
                    'order_index' => $index,
                ]);
            }

            return $item->load('options');
        });
    }

    /**
     * @param  array<string, mixed>  $data  Divalidasi UpdateQuestionBankItemRequest.
     */
    public function updateItem(QuestionBankItem $item, array $data): QuestionBankItem
    {
        return DB::transaction(function () use ($item, $data): QuestionBankItem {
            $item->update(array_filter([
                'course_id' => array_key_exists('course_id', $data) ? $data['course_id'] : null,
                'question_text' => $data['question_text'] ?? null,
                'points' => $data['points'] ?? null,
            ], fn ($value) => $value !== null));

            if (isset($data['options'])) {
                $item->options()->delete();

                foreach ((array) $data['options'] as $index => $option) {
                    $item->options()->create([
                        'option_text' => $option['option_text'],
                        'is_correct' => (bool) $option['is_correct'],
                        'order_index' => $index,
                    ]);
                }
            }

            return $item->fresh('options');
        });
    }

    public function deleteItem(QuestionBankItem $item): void
    {
        $item->delete();
    }

    /**
     * Menyalin item bank terpilih menjadi ExamQuestion baru pada sebuah
     * ujian. Item yang sudah pernah diterapkan ke ujian yang sama dilewati
     * (tidak diduplikasi) — lihat kolom exam_questions.question_bank_item_id.
     *
     * @param  array<int, string>  $questionBankItemIds
     * @return Collection<int, ExamQuestion>
     */
    public function applyToExam(Exam $exam, array $questionBankItemIds): Collection
    {
        if ($exam->is_published) {
            throw new ConflictException('Ujian yang sudah dipublikasikan tidak dapat diubah konfigurasinya.');
        }

        return DB::transaction(function () use ($exam, $questionBankItemIds): Collection {
            $alreadyApplied = $exam->questions()
                ->whereIn('question_bank_item_id', $questionBankItemIds)
                ->pluck('question_bank_item_id')
                ->all();

            $items = QuestionBankItem::query()
                ->with('options')
                ->whereIn('id', $questionBankItemIds)
                ->whereNotIn('id', $alreadyApplied)
                ->get();

            $nextOrderIndex = $exam->questions()->count();
            $created = new Collection;

            foreach ($items as $item) {
                $question = $exam->questions()->create([
                    'question_bank_item_id' => $item->id,
                    'question_text' => $item->question_text,
                    'points' => $item->points,
                    'order_index' => $nextOrderIndex++,
                    'is_selected' => true,
                ]);

                foreach ($item->options as $option) {
                    $question->options()->create([
                        'option_text' => $option->option_text,
                        'is_correct' => $option->is_correct,
                        'order_index' => $option->order_index,
                    ]);
                }

                $created->push($question->load('options'));
            }

            return $created;
        });
    }
}
