<?php

namespace Modules\HumanResource\Policies;

use App\Models\User;
use Modules\HumanResource\Models\EmployeeTransfer;

/**
 * Penempatan & mutasi. Pembatalan mutasi terjadwal memakai hr_transfers.update.
 */
class EmployeeTransferPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('hr_transfers.read');
    }

    public function view(User $user, EmployeeTransfer $record): bool
    {
        return $user->hasPermissionTo('hr_transfers.read');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('hr_transfers.create');
    }

    public function update(User $user, EmployeeTransfer $record): bool
    {
        return $user->hasPermissionTo('hr_transfers.update');
    }

    public function delete(User $user, EmployeeTransfer $record): bool
    {
        return $user->hasPermissionTo('hr_transfers.update');
    }
}
