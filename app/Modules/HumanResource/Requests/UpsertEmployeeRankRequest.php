<?php

namespace Modules\HumanResource\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpsertEmployeeRankRequest extends FormRequest
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
            'rank_id' => ['required', 'string'],
            'decree_number' => ['nullable', 'string', 'max:100'],
            'decree_date' => ['nullable', 'date'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'decree_file_id' => ['nullable', 'string'],
            'is_current' => ['sometimes', 'boolean'],
        ];
    }
}
