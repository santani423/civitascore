<?php

namespace Modules\UserManagement\Actions;

use App\Models\User;
use App\Support\Http\Exceptions\ConflictException;
use App\Support\Tenancy\TenantContext;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Modules\UserManagement\Models\Role;
use Modules\UserManagement\Models\UserRole;

class AssignRoleAction
{
    public function __construct(private readonly TenantContext $tenant) {}

    /**
     * $scopeType/$scopeId restrict the grant to one specific record (e.g.
     * later a Faculty) instead of the whole system — leave both null for a
     * global grant. The same role may be assigned to a user more than once
     * as long as each grant differs by scope or by tenant (a user with
     * memberships in two universities can hold the same role in both,
     * independently).
     */
    public function execute(
        User $user,
        Role $role,
        ?User $assignedBy = null,
        ?CarbonInterface $expiresAt = null,
        ?string $scopeType = null,
        ?string $scopeId = null,
    ): UserRole {
        $universityId = $this->tenant->universityId();

        $alreadyAssigned = UserRole::query()
            ->where('user_id', $user->id)
            ->where('role_id', $role->id)
            ->where('university_id', $universityId)
            ->where('scope_type', $scopeType)
            ->where('scope_id', $scopeId)
            ->exists();

        if ($alreadyAssigned) {
            throw new ConflictException('Role ini sudah dimiliki pengguna pada scope yang sama.');
        }

        return DB::transaction(fn (): UserRole => UserRole::create([
            'user_id' => $user->id,
            'role_id' => $role->id,
            'university_id' => $universityId,
            'scope_type' => $scopeType,
            'scope_id' => $scopeId,
            'assigned_by' => $assignedBy?->id,
            'assigned_at' => now(),
            'expires_at' => $expiresAt,
        ]));
    }
}
