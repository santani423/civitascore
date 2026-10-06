<?php

namespace Modules\HumanResource\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\HumanResource\Models\Rank;

/**
 * @extends Factory<Rank>
 */
class RankFactory extends Factory
{
    protected $model = Rank::class;

    public function definition(): array
    {
        return [
            'name' => 'Penata '.fake()->unique()->word(),
            'grade' => fake()->unique()->bothify('III/?#'),
            'level' => fake()->numberBetween(1, 17),
            'is_active' => true,
        ];
    }
}
