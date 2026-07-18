<?php

namespace Modules\SystemSetting\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\SystemSetting\Models\FeatureFlag;

/**
 * @extends Factory<FeatureFlag>
 */
class FeatureFlagFactory extends Factory
{
    protected $model = FeatureFlag::class;

    public function definition(): array
    {
        return [
            'key' => fake()->unique()->slug(2),
            'name' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'is_enabled' => false,
            'updated_by' => null,
        ];
    }
}
