<?php

namespace Modules\HumanResource\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Academic\Models\Employee;
use Modules\HumanResource\Requests\Concerns\EmployeeRules;

/**
 * Ubah data pegawai. Unit kerja & jabatan sengaja TIDAK bisa diubah dari
 * sini (prohibited) — perubahan keduanya wajib lewat Penempatan & Mutasi
 * atau Riwayat Jabatan, supaya riwayatnya selalu tercatat. Status aktif
 * juga lewat endpoint nonaktifkan/aktifkan (dengan alasan), bukan di sini.
 */
class UpdateHrEmployeeRequest extends FormRequest
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
        /** @var Employee $employee */
        $employee = $this->route('employee');

        return [
            ...$this->personalRules($employee->id),
            'employee_type' => ['prohibited'],
            'work_unit_id' => ['prohibited'],
            'position_id' => ['prohibited'],
            'is_active' => ['prohibited'],
            ...$this->lecturerRules($employee->isLecturer(), $employee->lecturer?->id),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            ...$this->employeeMessages(),
            'work_unit_id.prohibited' => 'Unit kerja diubah lewat menu Penempatan & Mutasi.',
            'position_id.prohibited' => 'Jabatan diubah lewat Riwayat Jabatan atau Mutasi.',
            'is_active.prohibited' => 'Gunakan aksi Nonaktifkan/Aktifkan untuk mengubah status pegawai.',
            'employee_type.prohibited' => 'Jenis pegawai tidak dapat diubah.',
        ];
    }
}
