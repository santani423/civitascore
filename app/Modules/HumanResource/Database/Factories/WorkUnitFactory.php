<?php

namespace Modules\HumanResource\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\HumanResource\Enums\WorkUnitType;
use Modules\HumanResource\Models\WorkUnit;

/**
 * @extends Factory<WorkUnit>
 */
class WorkUnitFactory extends Factory
{
    protected $model = WorkUnit::class;

    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->bothify('UK-###??')),
            'name' => 'Biro '.fake()->unique()->word(),
            'type' => WorkUnitType::Bureau,
            'is_active' => true,
        ];
    }
}
