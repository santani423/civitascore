<?php

namespace Modules\Academic\Policies;

use App\Models\User;

class GradePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('grades.read');
    }
}
