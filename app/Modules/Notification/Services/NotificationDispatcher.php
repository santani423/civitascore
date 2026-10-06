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
     * @param  array<string, string|null>  $data  lihat TemplatedNotification::$data
     */
    public function dispatch(User $user, string $eventKey, array $channels, array $placeholders = [], array $data = []): void
    {
        $user->notify(new TemplatedNotification($eventKey, $channels, $placeholders, $data));
    }
}
