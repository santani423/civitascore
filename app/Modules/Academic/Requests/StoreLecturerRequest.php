<?php

namespace Modules\Academic\Requests;

use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Academic\Enums\LecturerEducationLevel;
use Modules\Academic\Enums\LecturerEmploymentStatus;
use Modules\Academic\Enums\LecturerFunctionalRank;

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
        // Soft-deleted rows are ignored here: LecturerController::store()
        // restores a deleted lecturer with the same NIDN instead of
        // creating a duplicate.
        $universityId = app(TenantContext::class)->universityId();

        return [
            'faculty_id' => ['nullable', 'string'],
            'nidn' => [
                'required',
                'string',
                'max:50',
                Rule::unique('lecturers', 'nidn')->where('university_id', $universityId)->whereNull('deleted_at'),
            ],
            'nip' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('lecturers', 'nip')->where('university_id', $universityId)->whereNull('deleted_at'),
            ],
            'name' => ['required', 'string', 'max:255'],
            // An email is the login identifier, so it's required whenever an
            // account is provisioned (the default).
            'email' => [
                Rule::requiredIf(fn (): bool => $this->boolean('create_account', true)),
                'nullable',
                'email',
                'max:255',
                Rule::unique('lecturers', 'email')->where('university_id', $universityId)->whereNull('deleted_at'),
            ],
            'phone' => ['nullable', 'string', 'max:30'],
            'employment_status' => ['nullable', Rule::enum(LecturerEmploymentStatus::class)],
            'functional_rank' => ['nullable', Rule::enum(LecturerFunctionalRank::class)],
            'highest_education' => ['nullable', Rule::enum(LecturerEducationLevel::class)],
            'hired_at' => ['nullable', 'date'],
            'is_active' => ['boolean'],
            'create_account' => ['sometimes', 'boolean'],
            'password' => ['nullable', 'string', 'min:8', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.required' => 'Email wajib diisi untuk membuat akun login dosen.',
            'email.unique' => 'Email ini sudah dipakai dosen lain.',
            'nidn.unique' => 'NIDN ini sudah terdaftar.',
            'nip.unique' => 'NIP ini sudah terdaftar.',
        ];
    }
}
