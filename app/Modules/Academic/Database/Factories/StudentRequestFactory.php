<?php

namespace Modules\Academic\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Academic\Enums\StudentRequestStatus;
use Modules\Academic\Enums\StudentRequestType;
use Modules\Academic\Models\Student;
use Modules\Academic\Models\StudentRequest;

/**
 * @extends Factory<StudentRequest>
 */
class StudentRequestFactory extends Factory
{
    protected $model = StudentRequest::class;

    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'type' => StudentRequestType::Letter,
            'status' => StudentRequestStatus::Draft,
            'title' => 'Surat Keterangan Aktif Kuliah',
            'description' => fake()->sentence(),
            'payload' => ['letter_type' => 'active_student', 'purpose' => 'Beasiswa'],
        ];
    }
}
