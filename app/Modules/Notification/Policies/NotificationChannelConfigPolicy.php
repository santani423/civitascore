<?php

namespace Modules\Notification\Policies;

use App\Models\User;

class NotificationChannelConfigPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('notification_channels.read');
    }

    public function update(User $user): bool
    {
        return $user->hasPermissionTo('notification_channels.update');
    }
}
