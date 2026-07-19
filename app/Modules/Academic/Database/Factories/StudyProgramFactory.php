<?php

namespace Modules\Academic\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Academic\Models\Faculty;
use Modules\Academic\Models\StudyProgram;

/**
 * @extends Factory<StudyProgram>
 */
class StudyProgramFactory extends Factory
{
    protected $model = StudyProgram::class;

    public function definition(): array
    {
        return [
            'faculty_id' => Faculty::factory(),
            'code' => strtoupper(fake()->unique()->lexify('????')),
            'name' => fake()->words(3, true),
            'degree_level' => fake()->randomElement(['D3', 'S1', 'S2', 'S3']),
            'is_active' => true,
        ];
    }
}
