<?php

namespace Modules\Academic\Policies;

use App\Models\User;

class GradePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('grades.read');
    }

    /** Satu ability untuk upsert nilai — create dan update memakai endpoint yang sama. */
    public function manage(User $user): bool
    {
        return $user->hasPermissionTo('grades.create') || $user->hasPermissionTo('grades.update');
    }
}
