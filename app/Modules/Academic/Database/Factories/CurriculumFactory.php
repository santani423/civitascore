<?php

namespace Modules\Academic\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Academic\Models\Curriculum;
use Modules\Academic\Models\StudyProgram;

/**
 * @extends Factory<Curriculum>
 */
class CurriculumFactory extends Factory
{
    protected $model = Curriculum::class;

    public function definition(): array
    {
        return [
            'study_program_id' => StudyProgram::factory(),
            'name' => 'Kurikulum '.fake()->year(),
            'academic_year' => '2026/2027',
            'is_active' => true,
        ];
    }
}
