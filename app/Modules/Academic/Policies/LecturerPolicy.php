<?php

namespace Modules\Academic\Policies;

use App\Models\User;

class LecturerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('lecturers.read');
    }

    public function view(User $user): bool
    {
        return $user->hasPermissionTo('lecturers.read');
    }
}
