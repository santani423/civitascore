<?php

namespace Modules\Thesis\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Academic\Models\Lecturer;
use Modules\Academic\Models\Student;
use Modules\Thesis\Enums\ThesisStatus;
use Modules\Thesis\Enums\ThesisType;
use Modules\Thesis\Models\Thesis;

/**
 * @extends Factory<Thesis>
 */
class ThesisFactory extends Factory
{
    protected $model = Thesis::class;

    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'supervisor_lecturer_id' => Lecturer::factory(),
            'title' => fake()->sentence(6),
            'thesis_type' => ThesisType::Skripsi,
            'status' => ThesisStatus::Bimbingan,
            'submitted_at' => now(),
            'completed_at' => null,
        ];
    }
}
