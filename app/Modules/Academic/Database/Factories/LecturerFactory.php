<?php

namespace Modules\Academic\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Academic\Models\Faculty;
use Modules\Academic\Models\Lecturer;

/**
 * @extends Factory<Lecturer>
 */
class LecturerFactory extends Factory
{
    protected $model = Lecturer::class;

    public function definition(): array
    {
        return [
            'faculty_id' => Faculty::factory(),
            'nidn' => fake()->unique()->numerify('##########'),
            'name' => 'Dr. '.fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'is_active' => true,
        ];
    }
}
