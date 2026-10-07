<?php

namespace Modules\HumanResource\Observers;

use Modules\Academic\Models\Lecturer;
use Modules\HumanResource\Services\EmployeeService;

/**
 * Menjaga satu identitas untuk dosen: dosen yang dibuat/diubah dari Modul
 * Akademik (menu Dosen) otomatis punya/menyinkronkan baris pegawai di Modul
 * SDM, jadi tidak ada data dosen ganda yang saling berbeda.
 */
class LecturerObserver
{
    public function created(Lecturer $lecturer): void
    {
        if ($lecturer->employee_id === null) {
            app(EmployeeService::class)->ensureEmployeeForLecturer($lecturer);
        }
    }

    public function updated(Lecturer $lecturer): void
    {
        $employee = $lecturer->employee;

        if ($employee === null || ! $lecturer->wasChanged(['name', 'email', 'faculty_id', 'is_active'])) {
            return;
        }

        $employee->fill([
            'name' => $lecturer->name,
            'email' => $lecturer->email,
            'faculty_id' => $lecturer->faculty_id,
            'is_active' => $lecturer->is_active,
        ]);

        if ($employee->isDirty()) {
            app(EmployeeService::class)->syncLegacyColumns($employee);
            $employee->save();
        }
    }

    /**
     * Dosen dihapus dari Modul Akademik → data kepegawaiannya diarsipkan
     * (soft delete), bukan dihapus permanen.
     */
    public function deleted(Lecturer $lecturer): void
    {
        $lecturer->employee?->delete();
    }
}
