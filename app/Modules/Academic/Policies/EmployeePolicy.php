<?php

namespace Modules\Academic\Policies;

use App\Models\User;

class EmployeePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('employees.read');
    }

    public function view(User $user): bool
    {
        return $user->hasPermissionTo('employees.read');
    }
}
