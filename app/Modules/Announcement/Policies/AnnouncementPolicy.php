<?php

namespace Modules\Announcement\Policies;

use App\Models\User;

class AnnouncementPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('announcements.read');
    }

    public function view(User $user): bool
    {
        return $user->hasPermissionTo('announcements.read');
    }
}
