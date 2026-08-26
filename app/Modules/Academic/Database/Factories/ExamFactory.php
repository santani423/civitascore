<?php

namespace Modules\Academic\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Academic\Enums\QuestionSelectionMode;
use Modules\Academic\Models\ClassSection;
use Modules\Academic\Models\Exam;

/**
 * @extends Factory<Exam>
 */
class ExamFactory extends Factory
{
    protected $model = Exam::class;

    public function definition(): array
    {
        return [
            'class_section_id' => ClassSection::factory(),
            'title' => 'Ujian '.fake()->words(2, true),
            'duration_minutes' => fake()->numberBetween(60, 120),
            'questions_per_participant' => 10,
            'question_selection_mode' => QuestionSelectionMode::All,
            'randomize_questions' => false,
            'randomize_options' => false,
            'allow_back_navigation' => true,
            'show_result_after_submission' => true,
            'max_attempts' => 1,
            'is_published' => false,
        ];
    }
}
