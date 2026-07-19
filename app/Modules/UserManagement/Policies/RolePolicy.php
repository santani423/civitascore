<?php

namespace Modules\UserManagement\Policies;

use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Modules\UserManagement\Models\Role;

class RolePolicy
{
    public function __construct(private readonly TenantContext $tenant) {}

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('roles.read');
    }

    public function view(User $user, Role $role): bool
    {
        return $user->hasPermissionTo('roles.read') && $this->isVisible($user, $role);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('roles.create');
    }

    public function update(User $user, Role $role): bool
    {
        return $user->hasPermissionTo('roles.update') && $this->isMutable($user, $role);
    }

    public function delete(User $user, Role $role): bool
    {
        // Whether this *specific* role is deletable (is_system) is a
        // business-state rule, not an authorization rule — RoleService
        // enforces that and reports it as a 409 conflict, not a 403.
        return $user->hasPermissionTo('roles.delete') && $this->isMutable($user, $role);
    }

    public function managePermissions(User $user, Role $role): bool
    {
        return $user->hasPermissionTo('roles.update') && $this->isMutable($user, $role);
    }

    /**
     * Global template roles (university_id null, e.g. super_admin/staff)
     * are readable from every context; a tenant's own custom role is
     * readable from that tenant's context (or platform/super_admin).
     */
    private function isVisible(User $user, Role $role): bool
    {
        if ($user->hasRole('super_admin')) {
            return true;
        }

        return $role->university_id === null || $role->university_id === $this->tenant->universityId();
    }

    /**
     * Mutation is stricter than visibility: a global template is only
     * mutable by a Super Admin, never by a tenant admin acting inside a
     * tenant context — a role is only mutable by an ordinary actor when its
     * own university_id exactly matches the currently resolved tenant.
     */
    private function isMutable(User $user, Role $role): bool
    {
        if ($user->hasRole('super_admin')) {
            return true;
        }

        return $role->university_id === $this->tenant->universityId();
    }
}
