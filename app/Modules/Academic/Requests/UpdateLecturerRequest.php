<?php

namespace Modules\Academic\Requests;

use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Academic\Enums\LecturerEducationLevel;
use Modules\Academic\Enums\LecturerEmploymentStatus;
use Modules\Academic\Enums\LecturerFunctionalRank;

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
        $unique = fn (string $column) => Rule::unique('lecturers', $column)
            ->where('university_id', $universityId)
            ->whereNull('deleted_at')
            ->ignore($this->route('lecturer'));

        return [
            'faculty_id' => ['sometimes', 'nullable', 'string'],
            'nidn' => ['sometimes', 'string', 'max:50', $unique('nidn')],
            'nip' => ['sometimes', 'nullable', 'string', 'max:50', $unique('nip')],
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'nullable', 'email', 'max:255', $unique('email')],
            'phone' => ['sometimes', 'nullable', 'string', 'max:30'],
            'employment_status' => ['sometimes', 'nullable', Rule::enum(LecturerEmploymentStatus::class)],
            'functional_rank' => ['sometimes', 'nullable', Rule::enum(LecturerFunctionalRank::class)],
            'highest_education' => ['sometimes', 'nullable', Rule::enum(LecturerEducationLevel::class)],
            'hired_at' => ['sometimes', 'nullable', 'date'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.unique' => 'Email ini sudah dipakai dosen lain.',
            'nidn.unique' => 'NIDN ini sudah terdaftar.',
            'nip.unique' => 'NIP ini sudah terdaftar.',
        ];
    }
}
