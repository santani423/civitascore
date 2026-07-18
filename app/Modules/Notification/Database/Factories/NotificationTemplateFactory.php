<?php

namespace Modules\Notification\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Notification\Enums\NotificationChannel;
use Modules\Notification\Models\NotificationTemplate;

/**
 * @extends Factory<NotificationTemplate>
 */
class NotificationTemplateFactory extends Factory
{
    protected $model = NotificationTemplate::class;

    public function definition(): array
    {
        return [
            'event_key' => fake()->unique()->slug(2),
            'name' => fake()->words(3, true),
            'channel' => NotificationChannel::Database,
            'subject' => fake()->sentence(),
            'body_template' => fake()->paragraph(),
            'is_active' => true,
        ];
    }
}
