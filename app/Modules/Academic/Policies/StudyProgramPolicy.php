<?php

namespace Modules\Academic\Policies;

use App\Models\User;

class StudyProgramPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('study_programs.read');
    }

    public function view(User $user): bool
    {
        return $user->hasPermissionTo('study_programs.read');
    }
}
