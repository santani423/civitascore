<?php

namespace Modules\HumanResource\Requests\Concerns;

use App\Support\Tenancy\TenantContext;
use Illuminate\Validation\Rule;
use Modules\HumanResource\Enums\EducationLevel;
use Modules\HumanResource\Enums\EmploymentStatus;
use Modules\HumanResource\Enums\Gender;
use Modules\HumanResource\Enums\LecturerStatus;
use Modules\HumanResource\Enums\StaffCategory;

/**
 * Aturan validasi bersama form pegawai (tambah & ubah). Unik selalu
 * komposit per universitas (pola StoreLecturerRequest) — NIP/NIK yang sama
 * boleh ada di universitas lain.
 */
trait EmployeeRules
{
    /**
     * @return array<string, mixed>
     */
    protected function personalRules(?string $ignoreEmployeeId = null): array
    {
        $universityId = app(TenantContext::class)->universityId();

        return [
            'name' => ['required', 'string', 'max:255'],
            'nik' => ['nullable', 'digits:16', Rule::unique('employees', 'nik')->where('university_id', $universityId)->ignore($ignoreEmployeeId)],
            'nip' => ['nullable', 'string', 'max:50', Rule::unique('employees', 'nip')->where('university_id', $universityId)->ignore($ignoreEmployeeId)],
            'email' => ['nullable', 'email', 'max:255'],
            'gender' => ['nullable', Rule::enum(Gender::class)],
            'birth_place' => ['nullable', 'string', 'max:100'],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+\-\s()]+$/'],
            'address' => ['nullable', 'string', 'max:1000'],
            'employment_status' => ['required', Rule::enum(EmploymentStatus::class)],
            'faculty_id' => ['nullable', 'string'],
            'study_program_id' => ['nullable', 'string'],
            'highest_education' => ['nullable', Rule::enum(EducationLevel::class)],
            'staff_category' => ['nullable', Rule::enum(StaffCategory::class)],
            'joined_at' => ['nullable', 'date'],
            'user_id' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function lecturerRules(bool $isLecturer, ?string $ignoreLecturerId = null): array
    {
        $universityId = app(TenantContext::class)->universityId();

        return [
            'nidn' => [
                $isLecturer ? 'required' : 'prohibited',
                'string',
                'max:50',
                Rule::unique('lecturers', 'nidn')->where('university_id', $universityId)->ignore($ignoreLecturerId),
            ],
            'nidk' => [$isLecturer ? 'nullable' : 'prohibited', 'string', 'max:50'],
            'serdos_number' => [$isLecturer ? 'nullable' : 'prohibited', 'string', 'max:50'],
            'expertise' => [$isLecturer ? 'nullable' : 'prohibited', 'string', 'max:255'],
            'lecturer_status' => [$isLecturer ? 'nullable' : 'prohibited', Rule::enum(LecturerStatus::class)],
            'teaching_started_at' => [$isLecturer ? 'nullable' : 'prohibited', 'date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function employeeMessages(): array
    {
        return [
            'nik.digits' => 'NIK harus 16 digit angka.',
            'nik.unique' => 'NIK sudah terdaftar pada pegawai lain.',
            'nip.unique' => 'NIP sudah terdaftar pada pegawai lain.',
            'nidn.unique' => 'NIDN sudah terdaftar pada dosen lain.',
            'nidn.required' => 'NIDN wajib diisi untuk dosen.',
            'phone.regex' => 'Nomor telepon hanya boleh berisi angka, spasi, +, -, dan tanda kurung.',
            'birth_date.before' => 'Tanggal lahir harus sebelum hari ini.',
        ];
    }
}
