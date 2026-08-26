<?php

namespace Modules\Academic\Requests;

use Closure;
use Illuminate\Foundation\Http\FormRequest;

class UpdateExamQuestionRequest extends FormRequest
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
            'question_text' => ['sometimes', 'string'],
            'points' => ['sometimes', 'numeric', 'min:0'],
            'order_index' => ['sometimes', 'integer', 'min:0'],
            'is_selected' => ['sometimes', 'boolean'],
            'options' => [
                'sometimes',
                'array',
                'min:2',
                function (string $attribute, array $value, Closure $fail): void {
                    $correctCount = collect($value)->filter(fn ($option) => (bool) ($option['is_correct'] ?? false))->count();

                    if ($correctCount !== 1) {
                        $fail('Setiap soal harus memiliki tepat satu jawaban benar.');
                    }
                },
            ],
            'options.*.option_text' => ['required_with:options', 'string'],
            'options.*.is_correct' => ['required_with:options', 'boolean'],
        ];
    }
}
