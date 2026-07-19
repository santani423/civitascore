<?php

namespace Modules\Academic\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Academic\Models\AcademicTerm;
use Modules\Academic\Models\ClassSection;
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
            'course_name' => fake()->words(3, true),
            'class_code' => strtoupper(fake()->unique()->bothify('??-###')),
            'capacity' => fake()->numberBetween(25, 50),
            'is_active' => true,
        ];
    }
}
