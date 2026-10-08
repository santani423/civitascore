<?php

namespace Modules\HumanResource\Requests;

use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\HumanResource\Enums\AcademicRank;
use Modules\HumanResource\Enums\EmployeeType;
use Modules\HumanResource\Requests\Concerns\EmployeeRules;

class StoreHrEmployeeRequest extends FormRequest
{
    use EmployeeRules;

    /**
     * Izin per jenis pegawai dicek sebelum validasi, supaya role tanpa izin
     * dosen mendapat 403 — bukan pesan validasi form dosen (mis. email wajib
     * untuk akun login). Jenis tidak valid dibiarkan ditolak validasi.
     */
    public function authorize(): bool
    {
        $type = EmployeeType::tryFrom((string) $this->input('employee_type'));

        return $type === null || (bool) $this->user()?->can('hr.employees.create', $type);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $isLecturer = $this->input('employee_type') === EmployeeType::Lecturer->value;
        $universityId = app(TenantContext::class)->universityId();

        return [
            'employee_type' => ['required', Rule::enum(EmployeeType::class)],
            ...$this->personalRules(),
            // Dosen otomatis dibuatkan akun login (kecuali create_account
            // false atau menautkan akun yang ada lewat user_id) — email
            // adalah identitas login-nya, jadi wajib dan unik antar dosen
            // (pola StoreLecturerRequest).
            'email' => [
                Rule::requiredIf(fn (): bool => $isLecturer && $this->boolean('create_account', true) && ! $this->filled('user_id')),
                'nullable',
                'email',
                'max:255',
                ...($isLecturer ? [Rule::unique('lecturers', 'email')->where('university_id', $universityId)->whereNull('deleted_at')] : []),
            ],
            'create_account' => [$isLecturer ? 'sometimes' : 'prohibited', 'boolean'],
            'password' => [$isLecturer ? 'nullable' : 'prohibited', 'string', 'min:8', 'max:255'],
            'work_unit_id' => ['nullable', 'string'],
            'position_id' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
            ...$this->lecturerRules($isLecturer),
            ...$this->staffRules($this->input('employee_type') === EmployeeType::Staff->value, creating: true),
            'academic_rank' => [$isLecturer ? 'nullable' : 'prohibited', Rule::enum(AcademicRank::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            ...$this->employeeMessages(),
            'email.required' => 'Email wajib diisi untuk membuat akun login dosen.',
            'email.unique' => 'Email ini sudah dipakai dosen lain.',
            'create_account.prohibited' => 'Akun login otomatis hanya dibuat untuk dosen.',
            'password.prohibited' => 'Password awal hanya berlaku untuk akun login dosen.',
        ];
    }
}
