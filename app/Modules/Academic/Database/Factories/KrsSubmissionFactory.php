<?php

namespace Modules\Academic\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Academic\Enums\KrsSubmissionStatus;
use Modules\Academic\Models\AcademicTerm;
use Modules\Academic\Models\KrsSubmission;
use Modules\Academic\Models\Student;

/**
 * @extends Factory<KrsSubmission>
 */
class KrsSubmissionFactory extends Factory
{
    protected $model = KrsSubmission::class;

    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'academic_term_id' => AcademicTerm::factory(),
            'status' => KrsSubmissionStatus::Draft,
            'total_credits' => 0,
        ];
    }
}
