<?php

namespace Modules\SystemSetting\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\SystemSetting\Enums\SettingValueType;
use Modules\SystemSetting\Models\SystemSetting;

/**
 * @extends Factory<SystemSetting>
 */
class SystemSettingFactory extends Factory
{
    protected $model = SystemSetting::class;

    public function definition(): array
    {
        return [
            'key' => fake()->unique()->slug(2),
            'value' => (string) fake()->numberBetween(1, 100),
            'type' => SettingValueType::Integer,
            'group' => 'general',
            'description' => fake()->sentence(),
            'is_public' => false,
            'updated_by' => null,
        ];
    }
}
