<?php

namespace Modules\Tenancy\Policies;

use App\Models\User;

/**
 * Platform-level policy — university management is a Super Admin
 * responsibility (see docs on the platform vs tenant API split), never
 * granted to ordinary tenant roles.
 */
class UniversityPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('platform_universities.read');
    }

    public function view(User $user): bool
    {
        return $user->hasPermissionTo('platform_universities.read');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('platform_universities.create');
    }

    public function update(User $user): bool
    {
        return $user->hasPermissionTo('platform_universities.update');
    }
}
