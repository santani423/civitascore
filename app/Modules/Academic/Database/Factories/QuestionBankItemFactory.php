<?php

namespace Modules\Academic\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Academic\Models\QuestionBankItem;

/**
 * @extends Factory<QuestionBankItem>
 */
class QuestionBankItemFactory extends Factory
{
    protected $model = QuestionBankItem::class;

    public function definition(): array
    {
        return [
            'course_id' => null,
            'question_text' => fake()->sentence().'?',
            'points' => 1,
        ];
    }
}
