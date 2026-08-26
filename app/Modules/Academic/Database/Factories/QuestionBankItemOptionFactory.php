<?php

namespace Modules\Academic\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Academic\Models\QuestionBankItem;
use Modules\Academic\Models\QuestionBankItemOption;

/**
 * @extends Factory<QuestionBankItemOption>
 */
class QuestionBankItemOptionFactory extends Factory
{
    protected $model = QuestionBankItemOption::class;

    public function definition(): array
    {
        return [
            'question_bank_item_id' => QuestionBankItem::factory(),
            'option_text' => fake()->words(3, true),
            'is_correct' => false,
            'order_index' => 0,
        ];
    }
}
