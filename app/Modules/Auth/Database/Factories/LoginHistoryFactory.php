<?php

namespace Modules\Auth\Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Auth\Enums\LoginHistoryStatus;
use Modules\Auth\Models\LoginHistory;

/**
 * @extends Factory<LoginHistory>
 */
class LoginHistoryFactory extends Factory
{
    protected $model = LoginHistory::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'email_attempted' => fake()->safeEmail(),
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
            'user_device_id' => null,
            'status' => LoginHistoryStatus::Success,
            'failure_reason' => null,
        ];
    }
}
