<?php

namespace Modules\SystemSetting\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\SystemSetting\Enums\SettingValueType;
use Modules\SystemSetting\Models\ModuleSetting;

/**
 * @extends Factory<ModuleSetting>
 */
class ModuleSettingFactory extends Factory
{
    protected $model = ModuleSetting::class;

    public function definition(): array
    {
        return [
            'module_key' => fake()->unique()->word(),
            'key' => 'is_enabled',
            'value' => 'true',
            'type' => SettingValueType::Boolean,
            'is_active' => true,
            'is_visible' => true,
            'is_editable' => true,
            'available_from' => null,
            'available_until' => null,
        ];
    }
}
