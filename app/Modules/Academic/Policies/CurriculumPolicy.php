<?php

namespace Modules\Academic\Policies;

use App\Models\User;

class CurriculumPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('curriculums.read');
    }

    public function view(User $user): bool
    {
        return $user->hasPermissionTo('curriculums.read');
    }
}
