<?php

namespace Modules\Academic\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Academic\Enums\ExamViolationType;

class RecordExamViolationRequest extends FormRequest
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
            'violation_type' => ['required', Rule::enum(ExamViolationType::class)],
            // Bebas-bentuk tapi kecil & tidak sensitif (spec §4) — mis. path
            // halaman saat kejadian. Divalidasi ukurannya saja di sini.
            'metadata' => ['nullable', 'array'],
        ];
    }
}
