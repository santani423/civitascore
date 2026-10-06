<?php

namespace Modules\Academic\Requests;

use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Academic\Enums\StudentStatus;

class StoreStudentRequest extends FormRequest
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
        // Uniqueness is per-tenant (`students` has a composite
        // (university_id, nim) unique index, not a bare nim unique) — a
        // plain Rule::unique('students', 'nim') would query past
        // TenantScoped's global scope and wrongly reject a NIM that's
        // merely taken at a *different* university.
        $universityId = app(TenantContext::class)->universityId();

        return [
            'study_program_id' => ['required', 'string'],
            'nim' => [
                'required',
                'string',
                'max:50',
                Rule::unique('students', 'nim')->where('university_id', $universityId),
            ],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'tanggal_lahir' => ['nullable', 'date'],
            'admission_year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'status' => ['required', Rule::enum(StudentStatus::class)],
            'enrolled_at' => ['required', 'date'],
        ];
    }
}
