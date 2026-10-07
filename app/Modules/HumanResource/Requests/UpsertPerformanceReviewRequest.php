<?php

namespace Modules\HumanResource\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\HumanResource\Enums\PerformanceStatus;
use Modules\HumanResource\Models\PerformanceReview;

class UpsertPerformanceReviewRequest extends FormRequest
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
        /** @var PerformanceReview|null $current */
        $current = $this->route('performanceReview');
        $employeeId = $current->employee_id ?? $this->input('employee_id');

        return [
            'employee_id' => [$current === null ? 'required' : 'prohibited', 'string'],
            'reviewer_employee_id' => ['nullable', 'string', 'different:employee_id'],
            'period' => [
                'required', 'string', 'max:50',
                Rule::unique('performance_reviews', 'period')->where('employee_id', $employeeId)->ignore($current?->id),
            ],
            'score' => ['required', 'numeric', 'min:0', 'max:100'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', Rule::enum(PerformanceStatus::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['period.unique' => 'Pegawai ini sudah memiliki penilaian untuk periode tersebut.'];
    }
}
