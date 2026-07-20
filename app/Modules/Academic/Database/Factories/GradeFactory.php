<?php

namespace Modules\Academic\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Academic\Enums\LetterGrade;
use Modules\Academic\Models\Grade;
use Modules\Academic\Models\KrsItem;

/**
 * @extends Factory<Grade>
 */
class GradeFactory extends Factory
{
    protected $model = Grade::class;

    public function definition(): array
    {
        return [
            'krs_item_id' => KrsItem::factory(),
            'letter_grade' => fake()->randomElement(LetterGrade::cases()),
            'score' => fake()->randomFloat(2, 40, 100),
            'submitted_at' => now(),
        ];
    }
}
