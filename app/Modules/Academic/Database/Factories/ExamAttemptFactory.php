<?php

namespace Modules\Academic\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Academic\Enums\ExamAttemptStatus;
use Modules\Academic\Models\Exam;
use Modules\Academic\Models\ExamAttempt;
use Modules\Academic\Models\KrsItem;

/**
 * @extends Factory<ExamAttempt>
 */
class ExamAttemptFactory extends Factory
{
    protected $model = ExamAttempt::class;

    public function definition(): array
    {
        return [
            'exam_id' => Exam::factory(),
            'krs_item_id' => KrsItem::factory(),
            'attempt_number' => 1,
            'status' => ExamAttemptStatus::InProgress,
            'question_order' => [],
            'option_order' => [],
            'started_at' => now(),
        ];
    }
}
