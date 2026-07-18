<?php

namespace Modules\UserManagement\Policies;

use App\Models\User;

class UserRolePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('user_roles.read');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('user_roles.create');
    }

    public function delete(User $user): bool
    {
        return $user->hasPermissionTo('user_roles.delete');
    }
}
