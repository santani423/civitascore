<?php

namespace Modules\SystemSetting\Policies;

use App\Models\User;

class FeatureFlagPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('feature_flags.read');
    }

    public function update(User $user): bool
    {
        return $user->hasPermissionTo('feature_flags.update');
    }
}
