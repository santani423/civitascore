<?php

namespace Modules\Academic\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Academic\Enums\LetterGrade;

/**
 * Validasi rentang nilai satu ujian (spec §8) — replace-all, jadi seluruh
 * array divalidasi sekaligus termasuk aturan lintas-baris (non-duplikat,
 * non-overlap) yang tidak bisa diekspresikan lewat rule per-field biasa.
 */
class UpsertExamGradeRangesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'ranges' => ['required', 'array', 'min:1'],
            'ranges.*.grade' => ['required', Rule::enum(LetterGrade::class)],
            'ranges.*.min_score' => ['required', 'numeric', 'min:0', 'max:100'],
            'ranges.*.max_score' => ['required', 'numeric', 'min:0', 'max:100', 'gte:ranges.*.min_score'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $ranges = collect($this->input('ranges', []));

            $duplicateGrades = $ranges->pluck('grade')->duplicates();
            if ($duplicateGrades->isNotEmpty()) {
                $validator->errors()->add('ranges', 'Setiap nilai huruf hanya boleh muncul satu kali.');
            }

            foreach ($ranges as $i => $a) {
                foreach ($ranges as $j => $b) {
                    if ($i >= $j || ! isset($a['min_score'], $a['max_score'], $b['min_score'], $b['max_score'])) {
                        continue;
                    }

                    $overlaps = (float) $a['min_score'] <= (float) $b['max_score'] && (float) $b['min_score'] <= (float) $a['max_score'];

                    if ($overlaps) {
                        $validator->errors()->add(
                            'ranges',
                            sprintf('Rentang nilai %s dan %s saling tumpang tindih.', $a['grade'] ?? '?', $b['grade'] ?? '?'),
                        );
                    }
                }
            }
        });
    }
}
