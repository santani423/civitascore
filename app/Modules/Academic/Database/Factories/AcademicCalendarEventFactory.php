<?php

namespace Modules\Academic\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Academic\Enums\AcademicCalendarCategory;
use Modules\Academic\Models\AcademicCalendarEvent;

/**
 * @extends Factory<AcademicCalendarEvent>
 */
class AcademicCalendarEventFactory extends Factory
{
    protected $model = AcademicCalendarEvent::class;

    public function definition(): array
    {
        $start = now()->addDays(fake()->numberBetween(1, 30))->startOfDay();

        return [
            'title' => 'Agenda '.fake()->words(2, true),
            'description' => null,
            'category' => AcademicCalendarCategory::Other,
            'start_date' => $start->toDateString(),
            'end_date' => $start->addDays(2)->toDateString(),
        ];
    }
}
