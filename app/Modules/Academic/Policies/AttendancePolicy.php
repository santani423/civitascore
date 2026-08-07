<?php

namespace Modules\Academic\Policies;

use App\Models\User;

class AttendancePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('attendance.read');
    }

    /** Satu ability untuk rekam kehadiran batch — create dan update (koreksi) memakai endpoint yang sama. */
    public function record(User $user): bool
    {
        return $user->hasPermissionTo('attendance.create') || $user->hasPermissionTo('attendance.update');
    }
}
