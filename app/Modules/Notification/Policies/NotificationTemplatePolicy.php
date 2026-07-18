<?php

namespace Modules\Notification\Policies;

use App\Models\User;

class NotificationTemplatePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('notification_templates.read');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('notification_templates.create');
    }

    public function update(User $user): bool
    {
        return $user->hasPermissionTo('notification_templates.update');
    }
}
