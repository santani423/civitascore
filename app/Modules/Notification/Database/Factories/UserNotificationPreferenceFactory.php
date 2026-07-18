<?php

namespace Modules\Notification\Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Notification\Enums\NotificationChannel;
use Modules\Notification\Models\UserNotificationPreference;

/**
 * @extends Factory<UserNotificationPreference>
 */
class UserNotificationPreferenceFactory extends Factory
{
    protected $model = UserNotificationPreference::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'channel' => NotificationChannel::Database,
            'notification_type' => '*',
            'is_enabled' => true,
        ];
    }
}
