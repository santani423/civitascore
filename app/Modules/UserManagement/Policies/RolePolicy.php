<?php

namespace Modules\UserManagement\Policies;

use App\Models\User;
use Modules\UserManagement\Models\Role;

class RolePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('roles.read');
    }

    public function view(User $user, Role $role): bool
    {
        return $user->hasPermissionTo('roles.read');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('roles.create');
    }

    public function update(User $user, Role $role): bool
    {
        return $user->hasPermissionTo('roles.update');
    }

    public function delete(User $user, Role $role): bool
    {
        // Whether this *specific* role is deletable (is_system) is a
        // business-state rule, not an authorization rule — RoleService
        // enforces that and reports it as a 409 conflict, not a 403.
        return $user->hasPermissionTo('roles.delete');
    }

    public function managePermissions(User $user, Role $role): bool
    {
        return $user->hasPermissionTo('roles.update');
    }
}
