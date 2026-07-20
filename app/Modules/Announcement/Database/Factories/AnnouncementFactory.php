<?php

namespace Modules\Announcement\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Announcement\Enums\AnnouncementTargetScope;
use Modules\Announcement\Models\Announcement;

/**
 * @extends Factory<Announcement>
 */
class AnnouncementFactory extends Factory
{
    protected $model = Announcement::class;

    public function definition(): array
    {
        return [
            'title' => fake()->sentence(5),
            'body' => fake()->paragraphs(3, true),
            'target_scope' => AnnouncementTargetScope::Universitas,
            'target_id' => null,
            'is_pinned' => false,
            'published_at' => now(),
            'created_by' => null,
        ];
    }
}
