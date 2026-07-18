<?php

namespace Modules\UserManagement\Policies;

use App\Models\User;
use Modules\UserManagement\Models\Permission;

class PermissionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('permissions.read');
    }

    public function view(User $user, Permission $permission): bool
    {
        return $user->hasPermissionTo('permissions.read');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('permissions.create');
    }

    public function update(User $user, Permission $permission): bool
    {
        return $user->hasPermissionTo('permissions.update');
    }

    public function delete(User $user, Permission $permission): bool
    {
        // Whether this *specific* permission is deletable (is_system) is a
        // business-state rule, not an authorization rule — PermissionService
        // enforces that and reports it as a 409 conflict, not a 403.
        return $user->hasPermissionTo('permissions.delete');
    }
}
