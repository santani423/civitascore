<?php

namespace Modules\AuditLog\Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\AuditLog\Models\ActivityLog;

/**
 * @extends Factory<ActivityLog>
 */
class ActivityLogFactory extends Factory
{
    protected $model = ActivityLog::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'subject_type' => null,
            'subject_id' => null,
            'log_name' => 'default',
            'description' => fake()->sentence(),
            'properties' => null,
        ];
    }
}
