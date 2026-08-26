<?php

namespace Modules\Academic\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Academic\Models\ExamAttempt;
use Modules\Academic\Models\ExamAttemptAnswer;
use Modules\Academic\Models\ExamQuestion;

/**
 * @extends Factory<ExamAttemptAnswer>
 */
class ExamAttemptAnswerFactory extends Factory
{
    protected $model = ExamAttemptAnswer::class;

    public function definition(): array
    {
        return [
            'exam_attempt_id' => ExamAttempt::factory(),
            'exam_question_id' => ExamQuestion::factory(),
            'exam_question_option_id' => null,
            'answered_at' => now(),
        ];
    }
}
