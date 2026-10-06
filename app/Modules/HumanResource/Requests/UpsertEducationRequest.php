<?php

namespace Modules\HumanResource\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\HumanResource\Enums\EducationLevel;

class UpsertEducationRequest extends FormRequest
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
            'level' => ['required', Rule::enum(EducationLevel::class)],
            'institution' => ['required', 'string', 'max:255'],
            'major' => ['nullable', 'string', 'max:255'],
            'entry_year' => ['nullable', 'integer', 'min:1950', "max:{$maxYear}"],
            'graduation_year' => ['nullable', 'integer', 'min:1950', "max:{$maxYear}", 'gte:entry_year'],
            'certificate_number' => ['nullable', 'string', 'max:100'],
            'gpa' => ['nullable', 'numeric', 'min:0', 'max:4'],
            'document_file_id' => ['nullable', 'string'],
        ];
    }
}
