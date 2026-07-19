<?php

namespace Modules\Tenancy\Policies;

use App\Models\User;
use Modules\Tenancy\Models\SupportSession;

class SupportSessionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('support_sessions.read');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('support_sessions.create');
    }

    /** Cuma Super Admin yang memulai sesi itu sendiri yang boleh mengakhirinya. */
    public function end(User $user, SupportSession $supportSession): bool
    {
        return $user->hasPermissionTo('support_sessions.create') && $user->id === $supportSession->super_admin_id;
    }
}
