<?php

namespace Modules\HumanResource\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\HumanResource\Enums\LecturerActivityType;

class UpsertLecturerActivityRequest extends FormRequest
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
        $maxYear = (int) date('Y') + 1;

        return [
            'type' => ['required', Rule::enum(LecturerActivityType::class)],
            'title' => ['required', 'string', 'max:255'],
            'role' => ['nullable', 'string', 'max:100'],
            'year' => ['required', 'integer', 'min:1950', "max:{$maxYear}"],
            'funding_source' => ['nullable', 'string', 'max:255'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'description' => ['nullable', 'string', 'max:2000'],
            'document_file_id' => ['nullable', 'string'],
        ];
    }
}
