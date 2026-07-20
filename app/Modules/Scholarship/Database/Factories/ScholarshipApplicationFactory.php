<?php

namespace Modules\Scholarship\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Academic\Models\Student;
use Modules\Scholarship\Enums\ScholarshipApplicationStatus;
use Modules\Scholarship\Models\Scholarship;
use Modules\Scholarship\Models\ScholarshipApplication;

/**
 * @extends Factory<ScholarshipApplication>
 */
class ScholarshipApplicationFactory extends Factory
{
    protected $model = ScholarshipApplication::class;

    public function definition(): array
    {
        return [
            'scholarship_id' => Scholarship::factory(),
            'student_id' => Student::factory(),
            'status' => ScholarshipApplicationStatus::Submitted,
            'submitted_at' => now(),
            'reviewed_at' => null,
            'notes' => null,
        ];
    }
}
