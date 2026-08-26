<?php

namespace Modules\Academic\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Academic\Models\ExamQuestion;
use Modules\Academic\Models\ExamQuestionOption;

/**
 * @extends Factory<ExamQuestionOption>
 */
class ExamQuestionOptionFactory extends Factory
{
    protected $model = ExamQuestionOption::class;

    public function definition(): array
    {
        return [
            'exam_question_id' => ExamQuestion::factory(),
            'option_text' => fake()->words(3, true),
            'is_correct' => false,
            'order_index' => 0,
        ];
    }
}
