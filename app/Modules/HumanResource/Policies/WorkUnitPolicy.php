<?php

namespace Modules\HumanResource\Policies;

use App\Models\User;
use Modules\HumanResource\Models\WorkUnit;

/**
 * Master unit kerja — dikelola bersama master jabatan (hr_positions.*).
 */
class WorkUnitPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('hr_positions.read');
    }

    public function view(User $user, WorkUnit $record): bool
    {
        return $user->hasPermissionTo('hr_positions.read');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('hr_positions.create');
    }

    public function update(User $user, WorkUnit $record): bool
    {
        return $user->hasPermissionTo('hr_positions.update');
    }

    public function delete(User $user, WorkUnit $record): bool
    {
        return $user->hasPermissionTo('hr_positions.delete');
    }
}
