<?php

namespace Modules\Scholarship\Policies;

use App\Models\User;

/** Reuses `scholarships.read` — applications aren't a separately-permissioned resource, see ScholarshipApplicationController's docblock. */
class ScholarshipApplicationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('scholarships.read');
    }
}
