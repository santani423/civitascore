<?php

namespace Modules\HumanResource\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\HumanResource\Enums\AcademicRank;
use Modules\HumanResource\Enums\EmployeeType;
use Modules\HumanResource\Requests\Concerns\EmployeeRules;

class StoreHrEmployeeRequest extends FormRequest
{
    use EmployeeRules;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $isLecturer = $this->input('employee_type') === EmployeeType::Lecturer->value;

        return [
            'employee_type' => ['required', Rule::enum(EmployeeType::class)],
            ...$this->personalRules(),
            'work_unit_id' => ['nullable', 'string'],
            'position_id' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
            ...$this->lecturerRules($isLecturer),
            'academic_rank' => [$isLecturer ? 'nullable' : 'prohibited', Rule::enum(AcademicRank::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->employeeMessages();
    }
}
