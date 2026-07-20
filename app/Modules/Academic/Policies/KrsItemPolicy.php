<?php

namespace Modules\Academic\Policies;

use App\Models\User;

class KrsItemPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('krs.read');
    }
}
