<?php

namespace Modules\Auth\Policies;

use App\Models\User;
use Modules\Auth\Models\UserDevice;

class UserDevicePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, UserDevice $device): bool
    {
        return $user->id === $device->user_id;
    }
}
