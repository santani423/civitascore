<?php

namespace Modules\Auth\Policies;

use App\Models\User;
use Modules\Auth\Models\UserSession;

class UserSessionPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function delete(User $user, UserSession $session): bool
    {
        return $user->id === $session->user_id;
    }
}
