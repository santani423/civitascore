<?php

namespace Modules\Academic\Policies;

use App\Models\User;

class CoursePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('courses.read');
    }

    public function view(User $user): bool
    {
        return $user->hasPermissionTo('courses.read');
    }
}
