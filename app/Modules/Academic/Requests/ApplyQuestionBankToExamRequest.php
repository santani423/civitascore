<?php

namespace Modules\Academic\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ApplyQuestionBankToExamRequest extends FormRequest
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
            'question_bank_item_ids' => ['required', 'array', 'min:1'],
            'question_bank_item_ids.*' => ['required', 'string'],
        ];
    }
}
