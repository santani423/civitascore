<?php

namespace Modules\Internship\Policies;

use App\Models\User;

class InternshipPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('internships.read');
    }

    public function view(User $user): bool
    {
        return $user->hasPermissionTo('internships.read');
    }
}
