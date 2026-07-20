<?php

namespace Modules\Academic\Policies;

use App\Models\User;

class AttendancePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('attendance.read');
    }
}
