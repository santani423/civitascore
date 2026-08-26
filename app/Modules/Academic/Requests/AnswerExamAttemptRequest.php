<?php

namespace Modules\Academic\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AnswerExamAttemptRequest extends FormRequest
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
            'exam_question_id' => ['required', 'string'],
            'exam_question_option_id' => ['nullable', 'string'],
        ];
    }
}
