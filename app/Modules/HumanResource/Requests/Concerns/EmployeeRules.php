<?php

namespace Modules\HumanResource\Requests\Concerns;

use App\Support\Tenancy\TenantContext;
use Illuminate\Validation\Rule;
use Modules\HumanResource\Enums\EducationLevel;
use Modules\HumanResource\Enums\EmploymentStatus;
use Modules\HumanResource\Enums\Gender;
use Modules\HumanResource\Enums\LecturerStatus;
use Modules\HumanResource\Enums\MaritalStatus;
use Modules\HumanResource\Enums\Religion;
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
            'joined_at' => ['nullable', 'date'],
            'user_id' => ['nullable', 'string'],
            'supervisor_employee_id' => ['nullable', 'string', Rule::notIn(array_filter([$ignoreEmployeeId]))],
            'front_title' => ['nullable', 'string', 'max:50'],
            'back_title' => ['nullable', 'string', 'max:100'],
            'religion' => ['nullable', Rule::enum(Religion::class)],
            'marital_status' => ['nullable', Rule::enum(MaritalStatus::class)],
            'emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+\-\s()]+$/'],
            'npwp' => ['nullable', 'string', 'regex:/^[0-9.\-]{15,20}$/'],
            'bank_name' => ['nullable', 'string', 'max:100'],
            'bank_account_number' => ['nullable', 'string', 'max:30', 'regex:/^[0-9\-]+$/'],
            'bank_account_name' => ['nullable', 'string', 'max:255'],
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
     * Profil tenaga kependidikan (§5.4). Kategori wajib saat tendik
     * ditambahkan dan tidak bisa dikosongkan lagi saat diubah — tanpa
     * kategori, rekap tendik per kategori tidak bisa dihitung.
     *
     * @return array<string, mixed>
     */
    protected function staffRules(bool $isStaff, bool $creating): array
    {
        if (! $isStaff) {
            return [
                'staff_category' => ['prohibited'],
                'assigned_facility' => ['prohibited'],
                'competency_summary' => ['prohibited'],
            ];
        }

        return [
            'staff_category' => [...($creating ? ['required'] : ['sometimes', 'required']), Rule::enum(StaffCategory::class)],
            'assigned_facility' => ['nullable', 'string', 'max:255'],
            'competency_summary' => ['nullable', 'string', 'max:2000'],
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
            'supervisor_employee_id.not_in' => 'Pegawai tidak dapat menjadi atasan dirinya sendiri.',
            'npwp.regex' => 'Format NPWP tidak valid (15–16 digit, boleh dengan titik/strip).',
            'bank_account_number.regex' => 'Nomor rekening hanya boleh berisi angka dan strip.',
            'staff_category.required' => 'Kategori tenaga kependidikan wajib dipilih.',
            'staff_category.prohibited' => 'Kategori tenaga kependidikan tidak berlaku untuk dosen.',
            'assigned_facility.prohibited' => 'Penugasan laboratorium/fasilitas hanya untuk tenaga kependidikan.',
            'competency_summary.prohibited' => 'Ringkasan kompetensi tendik tidak berlaku untuk dosen.',
        ];
    }
}
