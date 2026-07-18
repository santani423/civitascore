<?php

namespace Modules\Notification\Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Notification\Enums\NotificationChannel;
use Modules\Notification\Enums\NotificationLogStatus;
use Modules\Notification\Models\NotificationLog;

/**
 * @extends Factory<NotificationLog>
 */
class NotificationLogFactory extends Factory
{
    protected $model = NotificationLog::class;

    public function definition(): array
    {
        return [
            'notification_id' => (string) Str::ulid(),
            'notifiable_type' => User::class,
            'notifiable_id' => User::factory(),
            'channel' => NotificationChannel::Database,
            'event_key' => fake()->slug(2),
            'status' => NotificationLogStatus::Sent,
            'error_message' => null,
            'sent_at' => now(),
        ];
    }
}
