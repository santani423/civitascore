<?php

namespace Modules\UserManagement\Support;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Modules\UserManagement\Models\Permission;
use Modules\UserManagement\Models\Role;

/**
 * Single source of truth for "what can this user do", used by both the
 * `permission:` route middleware and every Policy. Resolves through
 * user_roles (ignoring expired grants) -> role_permissions -> permissions,
 * cached per user and invalidated whenever role/permission grants change
 * (see UserRole/RolePermission model observers).
 */
class PermissionRegistry
{
    private const CACHE_TTL_SECONDS = 3600;

    private const SUPER_ADMIN_SLUG = 'super_admin';

    /**
     * @return array{roles: array<int, string>, permissions: array<int, string>, roleGrants: array<int, array{role: string, scope_type: string|null, scope_id: string|null}>}
     */
    public function contextForUser(User $user): array
    {
        return Cache::tags(['permissions'])->remember(
            $this->cacheKey($user),
            self::CACHE_TTL_SECONDS,
            function () use ($user): array {
                $activeUserRoles = fn ($query) => $query
                    ->whereNull('user_roles.expires_at')
                    ->orWhere('user_roles.expires_at', '>', now());

                $roleGrants = Role::query()
                    ->join('user_roles', 'user_roles.role_id', '=', 'roles.id')
                    ->where('user_roles.user_id', $user->id)
                    ->where($activeUserRoles)
                    ->toBase()
                    ->get(['roles.slug', 'user_roles.scope_type', 'user_roles.scope_id'])
                    ->map(fn (object $row): array => [
                        'role' => $row->slug,
                        'scope_type' => $row->scope_type,
                        'scope_id' => $row->scope_id,
                    ])
                    ->all();

                $roles = array_values(array_unique(array_column($roleGrants, 'role')));

                $permissions = Permission::query()
                    ->join('role_permissions', 'role_permissions.permission_id', '=', 'permissions.id')
                    ->join('user_roles', 'user_roles.role_id', '=', 'role_permissions.role_id')
                    ->where('user_roles.user_id', $user->id)
                    ->where($activeUserRoles)
                    ->pluck('permissions.slug')
                    ->unique()
                    ->values()
                    ->all();

                return ['roles' => $roles, 'permissions' => $permissions, 'roleGrants' => $roleGrants];
            },
        );
    }

    /**
     * @return array<int, array{role: string, scope_type: string|null, scope_id: string|null}>
     */
    public function roleGrantsForUser(User $user): array
    {
        return $this->contextForUser($user)['roleGrants'];
    }

    /**
     * Like userHasRole(), but for a role grant restricted to one specific
     * record (e.g. "kaprodi" of a given study program). A global grant of
     * the same role (scope_type null) always satisfies a scoped check too
     * — it's a superset. Pass $scopeType/$scopeId as null to require a
     * global grant specifically.
     */
    public function userHasRoleInScope(User $user, string $roleSlug, ?string $scopeType, ?string $scopeId): bool
    {
        foreach ($this->roleGrantsForUser($user) as $grant) {
            if ($grant['role'] !== $roleSlug) {
                continue;
            }

            if ($grant['scope_type'] === null) {
                return true;
            }

            if ($grant['scope_type'] === $scopeType && $grant['scope_id'] === $scopeId) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<int, string>
     */
    public function rolesForUser(User $user): array
    {
        return $this->contextForUser($user)['roles'];
    }

    /**
     * @return array<int, string>
     */
    public function permissionsForUser(User $user): array
    {
        return $this->contextForUser($user)['permissions'];
    }

    public function userHasRole(User $user, string $roleSlug): bool
    {
        return in_array($roleSlug, $this->rolesForUser($user), true);
    }

    public function userHasPermission(User $user, string $permissionSlug): bool
    {
        // The single source of truth for the super_admin bypass: the
        // `permission:` middleware calls this directly (never through
        // Laravel's Gate), so AuthServiceProvider's Gate::before alone
        // would not cover it — that hook stays too, as a safety net for
        // any future ability check that doesn't go through here.
        if ($this->userHasRole($user, self::SUPER_ADMIN_SLUG)) {
            return true;
        }

        return in_array($permissionSlug, $this->permissionsForUser($user), true);
    }

    public function forgetCacheForUser(User $user): void
    {
        Cache::tags(['permissions'])->forget($this->cacheKey($user));
    }

    private function cacheKey(User $user): string
    {
        return "permissions:user:{$user->id}";
    }
}
