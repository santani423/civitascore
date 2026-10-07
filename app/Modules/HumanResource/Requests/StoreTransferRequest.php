<?php

namespace Modules\HumanResource\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTransferRequest extends FormRequest
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
            'employee_id' => ['required', 'string'],
            'to_work_unit_id' => ['nullable', 'required_without:to_position_id', 'string'],
            'to_position_id' => ['nullable', 'required_without:to_work_unit_id', 'string'],
            'effective_date' => ['required', 'date'],
            'decree_number' => ['nullable', 'string', 'max:100'],
            'document_file_id' => ['nullable', 'string'],
            'reason' => ['nullable', 'string', 'max:2000'],
            'hr_request_id' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'to_work_unit_id.required_without' => 'Isi unit kerja baru dan/atau jabatan baru.',
            'to_position_id.required_without' => 'Isi unit kerja baru dan/atau jabatan baru.',
        ];
    }
}
