<?php

namespace Modules\Academic\Policies;

use App\Models\User;

class StudentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('students.read');
    }

    public function view(User $user): bool
    {
        return $user->hasPermissionTo('students.read');
    }
}
