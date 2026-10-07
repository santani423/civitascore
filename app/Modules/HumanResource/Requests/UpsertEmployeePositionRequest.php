<?php

namespace Modules\HumanResource\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpsertEmployeePositionRequest extends FormRequest
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
            'position_id' => ['required', 'string'],
            'work_unit_id' => ['nullable', 'string'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'decree_number' => ['nullable', 'string', 'max:100'],
            'decree_file_id' => ['nullable', 'string'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'is_current' => ['sometimes', 'boolean'],
        ];
    }
}
