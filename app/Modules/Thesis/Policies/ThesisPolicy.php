<?php

namespace Modules\Thesis\Policies;

use App\Models\User;

class ThesisPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('theses.read');
    }

    public function view(User $user): bool
    {
        return $user->hasPermissionTo('theses.read');
    }
}
