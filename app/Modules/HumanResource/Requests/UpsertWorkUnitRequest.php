<?php

namespace Modules\HumanResource\Requests;

use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\HumanResource\Enums\WorkUnitType;
use Modules\HumanResource\Models\WorkUnit;

class UpsertWorkUnitRequest extends FormRequest
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
        /** @var WorkUnit|null $current */
        $current = $this->route('workUnit');

        return [
            'code' => ['required', 'string', 'max:50', Rule::unique('work_units', 'code')->where('university_id', app(TenantContext::class)->universityId())->ignore($current?->id)],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::enum(WorkUnitType::class)],
            'parent_id' => ['nullable', 'string', Rule::notIn(array_filter([$current?->id]))],
            'faculty_id' => ['nullable', 'string'],
            'study_program_id' => ['nullable', 'string'],
            'head_employee_id' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
