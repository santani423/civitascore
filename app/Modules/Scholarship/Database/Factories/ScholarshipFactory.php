<?php

namespace Modules\Scholarship\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Scholarship\Models\Scholarship;

/**
 * @extends Factory<Scholarship>
 */
class ScholarshipFactory extends Factory
{
    protected $model = Scholarship::class;

    public function definition(): array
    {
        return [
            'name' => 'Beasiswa '.fake()->words(2, true),
            'provider' => fake()->company(),
            'quota' => fake()->numberBetween(10, 100),
            'amount' => fake()->randomElement([1_000_000, 2_000_000, 3_000_000, 5_000_000]),
            'academic_year' => '2026/2027',
            'registration_start' => now()->subMonths(2),
            'registration_end' => now()->addMonth(),
            'is_active' => true,
        ];
    }
}
