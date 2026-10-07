<?php

namespace Modules\HumanResource\Policies;

use App\Models\User;
use Modules\HumanResource\Models\EmployeeContract;

/**
 * Kontrak kerja.
 */
class EmployeeContractPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('hr_contracts.read');
    }

    public function view(User $user, EmployeeContract $record): bool
    {
        return $user->hasPermissionTo('hr_contracts.read');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('hr_contracts.create');
    }

    public function update(User $user, EmployeeContract $record): bool
    {
        return $user->hasPermissionTo('hr_contracts.update');
    }

    public function delete(User $user, EmployeeContract $record): bool
    {
        return $user->hasPermissionTo('hr_contracts.delete');
    }
}
