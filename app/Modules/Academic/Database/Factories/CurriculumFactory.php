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
            // Unik — unique index (university_id, study_program_id, name) membuat
            // "Kurikulum {tahun acak}" sesekali bentrok saat >1 kurikulum dibuat
            // untuk prodi yang sama dalam satu test (flaky).
            'name' => 'Kurikulum '.fake()->unique()->numberBetween(1000, 999999),
            'academic_year' => '2026/2027',
            'is_active' => true,
        ];
    }
}
