<?php

namespace Modules\Alumni\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Academic\Models\Student;
use Modules\Alumni\Enums\AlumniEmploymentStatus;
use Modules\Alumni\Models\Alumni;

/**
 * @extends Factory<Alumni>
 */
class AlumniFactory extends Factory
{
    protected $model = Alumni::class;

    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'graduation_year' => (int) now()->format('Y'),
            'employment_status' => AlumniEmploymentStatus::Bekerja,
            'company_name' => fake()->company(),
            'job_title' => fake()->jobTitle(),
            'waiting_period_months' => fake()->numberBetween(1, 12),
            'is_verified' => false,
        ];
    }
}
