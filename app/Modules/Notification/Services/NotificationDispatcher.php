<?php

namespace Modules\Notification\Services;

use App\Models\User;
use Modules\Notification\Enums\NotificationChannel;
use Modules\Notification\Notifications\TemplatedNotification;

class NotificationDispatcher
{
    /**
     * @param  array<int, NotificationChannel>  $channels
     * @param  array<string, string>  $placeholders
     */
    public function dispatch(User $user, string $eventKey, array $channels, array $placeholders = []): void
    {
        $user->notify(new TemplatedNotification($eventKey, $channels, $placeholders));
    }
}
