<?php

namespace Modules\HumanResource\Policies;

use App\Models\User;
use Modules\HumanResource\Models\EmployeePosition;

/**
 * Riwayat jabatan.
 */
class EmployeePositionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('hr_positions.read') || $user->hasPermissionTo('hr_employees.read');
    }

    public function view(User $user, EmployeePosition $record): bool
    {
        return $user->hasPermissionTo('hr_positions.read') || $user->hasPermissionTo('hr_employees.read');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('hr_positions.create');
    }

    public function update(User $user, EmployeePosition $record): bool
    {
        return $user->hasPermissionTo('hr_positions.update');
    }

    public function delete(User $user, EmployeePosition $record): bool
    {
        return $user->hasPermissionTo('hr_positions.delete');
    }
}
