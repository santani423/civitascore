<?php

namespace Modules\Academic\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Academic\Models\Exam;
use Modules\Academic\Models\ExamQuestion;

/**
 * @extends Factory<ExamQuestion>
 */
class ExamQuestionFactory extends Factory
{
    protected $model = ExamQuestion::class;

    public function definition(): array
    {
        return [
            'exam_id' => Exam::factory(),
            'question_text' => fake()->sentence().'?',
            'points' => 1,
            'order_index' => 0,
            'is_selected' => true,
        ];
    }
}
