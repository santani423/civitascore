<?php

namespace Modules\Academic\Requests;

use Closure;
use Illuminate\Foundation\Http\FormRequest;

class StoreExamQuestionRequest extends FormRequest
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
            'question_text' => ['required', 'string'],
            'points' => ['nullable', 'numeric', 'min:0'],
            'order_index' => ['nullable', 'integer', 'min:0'],
            'is_selected' => ['nullable', 'boolean'],
            'options' => [
                'required',
                'array',
                'min:2',
                function (string $attribute, array $value, Closure $fail): void {
                    $correctCount = collect($value)->filter(fn ($option) => (bool) ($option['is_correct'] ?? false))->count();

                    if ($correctCount !== 1) {
                        $fail('Setiap soal harus memiliki tepat satu jawaban benar.');
                    }
                },
            ],
            'options.*.option_text' => ['required', 'string'],
            'options.*.is_correct' => ['required', 'boolean'],
        ];
    }
}
