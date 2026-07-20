<?php

namespace Modules\Academic\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Academic\Models\Course;
use Modules\Academic\Models\Curriculum;
use Modules\Academic\Models\StudyProgram;

/**
 * @extends Factory<Course>
 */
class CourseFactory extends Factory
{
    protected $model = Course::class;

    public function definition(): array
    {
        return [
            'study_program_id' => StudyProgram::factory(),
            // Tied to the same study program as this course (not an
            // independently-random one) — otherwise every test/seed call
            // that leaves curriculum_id to its default pollutes the
            // study_programs table with an orphaned extra row.
            'curriculum_id' => fn (array $attributes) => Curriculum::factory()->create([
                'study_program_id' => $attributes['study_program_id'],
            ])->id,
            'code' => strtoupper(fake()->unique()->bothify('???###')),
            'name' => fake()->words(3, true),
            'credits' => fake()->numberBetween(2, 4),
            'semester_level' => fake()->numberBetween(1, 8),
            'is_active' => true,
        ];
    }
}
