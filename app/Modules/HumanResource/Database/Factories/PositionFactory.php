<?php

namespace Modules\HumanResource\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\HumanResource\Enums\PositionType;
use Modules\HumanResource\Models\Position;

/**
 * @extends Factory<Position>
 */
class PositionFactory extends Factory
{
    protected $model = Position::class;

    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->bothify('JB-###??')),
            'name' => 'Kepala '.fake()->unique()->word(),
            'type' => PositionType::Structural,
            'is_active' => true,
        ];
    }
}
