<?php

namespace Modules\Academic\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Academic\Models\Assignment;
use Modules\Academic\Models\ClassSection;

/**
 * @extends Factory<Assignment>
 */
class AssignmentFactory extends Factory
{
    protected $model = Assignment::class;

    public function definition(): array
    {
        return [
            'class_section_id' => ClassSection::factory(),
            'title' => 'Tugas '.fake()->words(3, true),
            'description' => fake()->paragraph(),
            'due_at' => now()->addWeek(),
            'allow_late_submission' => false,
            'allow_resubmission' => true,
            'max_file_size_mb' => 10,
            'allowed_extensions' => ['pdf', 'docx'],
            'max_score' => 100,
            'is_published' => true,
            'published_at' => now(),
        ];
    }
}
