<?php

namespace Modules\HumanResource\Policies;

use App\Models\User;
use Modules\HumanResource\Models\LeaveRequest;

/**
 * Cuti & izin. Pegawai selalu boleh melihat/membatalkan cutinya sendiri
 * (self-service); selebihnya butuh izin hr_leave.*.
 */
class LeaveRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('hr_leave.read');
    }

    public function view(User $user, LeaveRequest $leave): bool
    {
        return $user->hasPermissionTo('hr_leave.read') || $this->owns($user, $leave);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('hr_leave.create');
    }

    public function update(User $user, LeaveRequest $leave): bool
    {
        return $this->owns($user, $leave) || $user->hasPermissionTo('hr_leave.create');
    }

    public function cancel(User $user, LeaveRequest $leave): bool
    {
        return $this->owns($user, $leave) || $user->hasPermissionTo('hr_leave.create');
    }

    public function approve(User $user, LeaveRequest $leave): bool
    {
        return $user->hasPermissionTo('hr_leave.approve');
    }

    public function reject(User $user, LeaveRequest $leave): bool
    {
        return $user->hasPermissionTo('hr_leave.reject');
    }

    private function owns(User $user, LeaveRequest $leave): bool
    {
        $ownerUserId = $leave->employee?->user_id;

        return $ownerUserId !== null && $ownerUserId === $user->id;
    }
}
