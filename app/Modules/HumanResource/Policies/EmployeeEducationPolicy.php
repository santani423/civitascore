<?php

namespace Modules\HumanResource\Policies;

use App\Models\User;
use Modules\HumanResource\Models\EmployeeEducation;

/**
 * Riwayat pendidikan adalah bagian data pegawai — dibaca dengan hr_employees.read, diubah dengan hr_employees.update.
 */
class EmployeeEducationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('hr_employees.read');
    }

    public function view(User $user, EmployeeEducation $record): bool
    {
        return $user->hasPermissionTo('hr_employees.read');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('hr_employees.update');
    }

    public function update(User $user, EmployeeEducation $record): bool
    {
        return $user->hasPermissionTo('hr_employees.update');
    }

    public function delete(User $user, EmployeeEducation $record): bool
    {
        return $user->hasPermissionTo('hr_employees.update');
    }
}
