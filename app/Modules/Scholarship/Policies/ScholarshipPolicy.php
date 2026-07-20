<?php

namespace Modules\Scholarship\Policies;

use App\Models\User;

class ScholarshipPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('scholarships.read');
    }

    public function view(User $user): bool
    {
        return $user->hasPermissionTo('scholarships.read');
    }
}
