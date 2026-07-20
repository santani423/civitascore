<?php

namespace Modules\Alumni\Policies;

use App\Models\User;

class AlumniPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('alumni.read');
    }

    public function view(User $user): bool
    {
        return $user->hasPermissionTo('alumni.read');
    }
}
