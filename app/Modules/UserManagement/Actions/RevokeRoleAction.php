<?php

namespace Modules\UserManagement\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\UserManagement\Models\Role;
use Modules\UserManagement\Models\UserRole;

class RevokeRoleAction
{
    /**
     * Revokes only the grant matching the given scope (default: the global
     * grant). A user holding the same role under several different scopes
     * keeps the others — pass the matching $scopeType/$scopeId to revoke a
     * specific one.
     */
    public function execute(User $user, Role $role, ?string $scopeType = null, ?string $scopeId = null): void
    {
        // Deleted individually (not via the relation's detach(), and not a
        // bulk query-builder delete) so UserRole::booted()'s `deleted` hook
        // actually fires and flushes the permission cache — both of those
        // alternatives perform a raw DELETE that bypasses model events.
        DB::transaction(function () use ($user, $role, $scopeType, $scopeId): void {
            UserRole::query()
                ->where('user_id', $user->id)
                ->where('role_id', $role->id)
                ->where('scope_type', $scopeType)
                ->where('scope_id', $scopeId)
                ->get()
                ->each(fn (UserRole $userRole) => $userRole->delete());
        });
    }
}
