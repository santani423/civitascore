<?php

namespace Modules\HumanResource\Policies;

use App\Models\User;
use Modules\Academic\Models\Employee;
use Modules\HumanResource\Enums\EmployeeType;

/**
 * Otorisasi master pegawai di Modul SDM. Menulis data dosen butuh izin
 * pegawai umum DAN izin khusus dosen (hr_lecturers.*); tenaga kependidikan
 * butuh hr_staff.*. Jadi role bisa dibatasi, mis. hanya mengelola tendik.
 * Didaftarkan sebagai Gate ability `hr.employees.*` (lihat
 * HumanResourceServiceProvider).
 */
class HrEmployeePolicy
{
    public function viewAny(User $user, ?EmployeeType $type = null): bool
    {
        return match ($type) {
            EmployeeType::Lecturer => $user->hasPermissionTo('hr_lecturers.read') || $user->hasPermissionTo('hr_employees.read'),
            EmployeeType::Staff => $user->hasPermissionTo('hr_staff.read') || $user->hasPermissionTo('hr_employees.read'),
            null => $user->hasPermissionTo('hr_employees.read'),
        };
    }

    public function view(User $user, Employee $employee): bool
    {
        return $this->viewAny($user, $employee->employee_type);
    }

    public function create(User $user, EmployeeType $type): bool
    {
        return $user->hasPermissionTo('hr_employees.create') && $user->hasPermissionTo($this->typePermission($type, 'create'));
    }

    public function update(User $user, Employee $employee): bool
    {
        return $user->hasPermissionTo('hr_employees.update') && $user->hasPermissionTo($this->typePermission($employee->employee_type, 'update'));
    }

    public function delete(User $user, Employee $employee): bool
    {
        return $user->hasPermissionTo('hr_employees.delete') && $user->hasPermissionTo($this->typePermission($employee->employee_type, 'delete'));
    }

    public function export(User $user): bool
    {
        return $user->hasPermissionTo('hr_employees.export');
    }

    private function typePermission(EmployeeType $type, string $action): string
    {
        return ($type === EmployeeType::Lecturer ? 'hr_lecturers.' : 'hr_staff.').$action;
    }
}
