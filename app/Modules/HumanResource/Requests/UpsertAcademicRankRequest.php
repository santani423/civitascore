<?php

namespace Modules\HumanResource\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\HumanResource\Enums\AcademicRank;

class UpsertAcademicRankRequest extends FormRequest
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
            'academic_rank' => ['required', Rule::enum(AcademicRank::class)],
            'credit_points' => ['nullable', 'numeric', 'min:0', 'max:9999'],
            'decree_number' => ['nullable', 'string', 'max:100'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'decree_file_id' => ['nullable', 'string'],
            'is_current' => ['sometimes', 'boolean'],
        ];
    }
}
