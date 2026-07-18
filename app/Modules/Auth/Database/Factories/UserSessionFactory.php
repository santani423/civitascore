<?php

namespace Modules\Auth\Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Auth\Models\UserSession;

/**
 * @extends Factory<UserSession>
 */
class UserSessionFactory extends Factory
{
    protected $model = UserSession::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'user_device_id' => null,
            'login_history_id' => null,
            'session_token' => Str::random(64),
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
            'last_activity_at' => now(),
            'revoked_at' => null,
            'revoked_by' => null,
        ];
    }
}
