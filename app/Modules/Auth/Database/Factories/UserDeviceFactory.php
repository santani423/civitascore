<?php

namespace Modules\Auth\Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Auth\Enums\DeviceType;
use Modules\Auth\Models\UserDevice;

/**
 * @extends Factory<UserDevice>
 */
class UserDeviceFactory extends Factory
{
    protected $model = UserDevice::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'device_identifier' => Str::uuid()->toString(),
            'device_name' => fake()->words(2, true),
            'device_type' => fake()->randomElement(DeviceType::cases()),
            'platform' => fake()->randomElement(['iOS', 'Android', 'Windows', 'macOS']),
            'push_token' => null,
            'is_trusted' => false,
            'last_used_at' => now(),
        ];
    }
}
