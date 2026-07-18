<?php

namespace Modules\UserManagement\Support;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Modules\UserManagement\Models\Role;
use Modules\UserManagement\Models\UserRole;

trait HasPermissions
{
    /**
     * @return BelongsToMany<Role, $this, UserRole>
     */
    public function roles(): BelongsToMany
    {
        // ->using() is required, not cosmetic — see Role::permissions() for
        // why: without it, attach()/sync() never generate UserRole's ULID.
        return $this->belongsToMany(Role::class, 'user_roles')
            ->using(UserRole::class)
            ->withPivot(['scope_type', 'scope_id', 'assigned_by', 'assigned_at', 'expires_at']);
    }

    public function hasRole(string $slug): bool
    {
        return app(PermissionRegistry::class)->userHasRole($this, $slug);
    }

    public function hasRoleInScope(string $slug, ?string $scopeType, ?string $scopeId): bool
    {
        return app(PermissionRegistry::class)->userHasRoleInScope($this, $slug, $scopeType, $scopeId);
    }

    public function hasPermissionTo(string $permissionSlug): bool
    {
        return app(PermissionRegistry::class)->userHasPermission($this, $permissionSlug);
    }
}
