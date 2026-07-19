<?php

namespace Modules\Academic\Policies;

use App\Models\User;

class ClassSectionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('classes.read');
    }

    public function view(User $user): bool
    {
        return $user->hasPermissionTo('classes.read');
    }
}
