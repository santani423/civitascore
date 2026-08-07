<?php

namespace Modules\Academic\Policies;

use App\Models\User;
use Modules\Academic\Models\KrsItem;

class KrsItemPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('krs.read');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('krs.create');
    }

    public function drop(User $user, KrsItem $krsItem): bool
    {
        return $user->hasPermissionTo('krs.update');
    }
}
