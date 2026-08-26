<?php

namespace Modules\Academic\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Academic\Enums\QuestionSelectionMode;

class StoreExamRequest extends FormRequest
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
            'class_section_id' => ['required', 'string'],
            'title' => ['required', 'string', 'max:255'],
            'duration_minutes' => ['required', 'integer', 'min:1'],
            'questions_per_participant' => ['required', 'integer', 'min:1'],
            'question_selection_mode' => ['required', Rule::enum(QuestionSelectionMode::class)],
            'randomize_questions' => ['boolean'],
            'randomize_options' => ['boolean'],
            'allow_back_navigation' => ['boolean'],
            'show_result_after_submission' => ['boolean'],
            'max_attempts' => ['integer', 'min:1'],
        ];
    }
}
