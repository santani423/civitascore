<?php

namespace Modules\Academic\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Academic\Enums\StudentStatus;
use Modules\Academic\Models\Student;
use Modules\Academic\Models\StudyProgram;

/**
 * @extends Factory<Student>
 */
class StudentFactory extends Factory
{
    protected $model = Student::class;

    public function definition(): array
    {
        $admissionYear = fake()->numberBetween(2022, 2026);

        return [
            'study_program_id' => StudyProgram::factory(),
            'nim' => fake()->unique()->numerify('##########'),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'tanggal_lahir' => fake()->dateTimeBetween('-25 years', '-17 years')->format('Y-m-d'),
            'admission_year' => $admissionYear,
            'status' => StudentStatus::Active,
            'enrolled_at' => "{$admissionYear}-08-01",
            'graduated_at' => null,
        ];
    }
}
