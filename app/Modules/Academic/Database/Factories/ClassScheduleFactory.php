<?php

namespace Modules\Academic\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Academic\Models\ClassSchedule;
use Modules\Academic\Models\ClassSection;

/**
 * @extends Factory<ClassSchedule>
 */
class ClassScheduleFactory extends Factory
{
    protected $model = ClassSchedule::class;

    public function definition(): array
    {
        $startHour = fake()->numberBetween(7, 16);

        return [
            'class_section_id' => ClassSection::factory(),
            'day_of_week' => fake()->numberBetween(1, 5),
            'start_time' => sprintf('%02d:00', $startHour),
            'end_time' => sprintf('%02d:40', $startHour + 1),
            'room' => 'R.'.fake()->numberBetween(101, 409),
        ];
    }
}
