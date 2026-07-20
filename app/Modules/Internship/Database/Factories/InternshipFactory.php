<?php

namespace Modules\Internship\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Academic\Models\Lecturer;
use Modules\Academic\Models\Student;
use Modules\Internship\Enums\InternshipProgramType;
use Modules\Internship\Enums\InternshipStatus;
use Modules\Internship\Models\Internship;

/**
 * @extends Factory<Internship>
 */
class InternshipFactory extends Factory
{
    protected $model = Internship::class;

    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'program_type' => InternshipProgramType::Magang,
            'institution_name' => fake()->company(),
            'position' => fake()->jobTitle(),
            'supervisor_lecturer_id' => Lecturer::factory(),
            'start_date' => now()->subMonths(3),
            'end_date' => null,
            'status' => InternshipStatus::Berlangsung,
            'sks_converted' => null,
        ];
    }
}
