<?php

namespace Modules\Academic\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Academic\Enums\QuestionSelectionMode;

class UpdateExamRequest extends FormRequest
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
            'title' => ['sometimes', 'string', 'max:255'],
            'duration_minutes' => ['sometimes', 'integer', 'min:1'],
            'questions_per_participant' => ['sometimes', 'integer', 'min:1'],
            'question_selection_mode' => ['sometimes', Rule::enum(QuestionSelectionMode::class)],
            'randomize_questions' => ['sometimes', 'boolean'],
            'randomize_options' => ['sometimes', 'boolean'],
            'allow_back_navigation' => ['sometimes', 'boolean'],
            'show_result_after_submission' => ['sometimes', 'boolean'],
            'max_attempts' => ['sometimes', 'integer', 'min:1'],
        ];
    }
}
