<?php

namespace Modules\Academic\Requests;

use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLecturerRequest extends FormRequest
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
        $universityId = app(TenantContext::class)->universityId();

        return [
            'faculty_id' => ['sometimes', 'nullable', 'string'],
            'nidn' => [
                'sometimes',
                'string',
                'max:50',
                Rule::unique('lecturers', 'nidn')->where('university_id', $universityId)->ignore($this->route('lecturer')),
            ],
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
