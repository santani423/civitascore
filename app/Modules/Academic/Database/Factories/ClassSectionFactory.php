<?php

namespace Modules\Academic\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Academic\Models\AcademicTerm;
use Modules\Academic\Models\ClassSection;
use Modules\Academic\Models\Course;
use Modules\Academic\Models\StudyProgram;

/**
 * @extends Factory<ClassSection>
 */
class ClassSectionFactory extends Factory
{
    protected $model = ClassSection::class;

    public function definition(): array
    {
        return [
            'study_program_id' => StudyProgram::factory(),
            'academic_term_id' => AcademicTerm::factory(),
            // Tied to the same study program as this class section — a
            // class section should never reference a course from a
            // different program, and defaulting to an independently-random
            // course would also pollute study_programs with an orphaned row.
            'course_id' => fn (array $attributes) => Course::factory()->create([
                'study_program_id' => $attributes['study_program_id'],
            ])->id,
            'class_code' => strtoupper(fake()->unique()->bothify('??-###')),
            'capacity' => fake()->numberBetween(25, 50),
            'is_active' => true,
        ];
    }
}
