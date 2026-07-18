<?php

namespace Modules\Notification\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Notification\Enums\NotificationChannel;
use Modules\Notification\Models\NotificationChannelConfig;

/**
 * @extends Factory<NotificationChannelConfig>
 */
class NotificationChannelConfigFactory extends Factory
{
    protected $model = NotificationChannelConfig::class;

    public function definition(): array
    {
        return [
            'code' => fake()->unique()->randomElement(NotificationChannel::cases()),
            'name' => fake()->words(2, true),
            'is_enabled' => true,
            'config' => null,
        ];
    }
}
