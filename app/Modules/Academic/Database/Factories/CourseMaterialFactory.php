<?php

namespace Modules\Academic\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Academic\Enums\CourseMaterialType;
use Modules\Academic\Models\ClassSection;
use Modules\Academic\Models\CourseMaterial;

/**
 * @extends Factory<CourseMaterial>
 */
class CourseMaterialFactory extends Factory
{
    protected $model = CourseMaterial::class;

    public function definition(): array
    {
        return [
            'class_section_id' => ClassSection::factory(),
            'meeting_number' => fake()->numberBetween(1, 14),
            'title' => 'Materi '.fake()->words(3, true),
            'description' => fake()->sentence(),
            'type' => CourseMaterialType::Link,
            'url' => 'https://example.com/materi/'.fake()->slug(),
            'is_published' => true,
            'published_at' => now(),
        ];
    }
}
