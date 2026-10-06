<?php

namespace Modules\Academic\Requests;

use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLecturerRequest extends FormRequest
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
        // Scoped like StoreStudentRequest's nim rule — `lecturers` has a
        // composite (university_id, nidn) unique index, not a bare nidn
        // unique, so a plain Rule::unique would query past TenantScoped's
        // global scope and wrongly reject a NIDN merely taken elsewhere.
        $universityId = app(TenantContext::class)->universityId();

        return [
            'faculty_id' => ['nullable', 'string'],
            'nidn' => [
                'required',
                'string',
                'max:50',
                Rule::unique('lecturers', 'nidn')->where('university_id', $universityId),
            ],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'is_active' => ['boolean'],
        ];
    }
}
