<?php

namespace Modules\Academic\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Academic\Enums\AttendanceStatus;
use Modules\Academic\Models\Attendance;
use Modules\Academic\Models\KrsItem;

/**
 * @extends Factory<Attendance>
 */
class AttendanceFactory extends Factory
{
    protected $model = Attendance::class;

    public function definition(): array
    {
        return [
            'krs_item_id' => KrsItem::factory(),
            'meeting_number' => fake()->numberBetween(1, 6),
            'meeting_date' => fake()->dateTimeBetween('-3 months', 'now'),
            'status' => AttendanceStatus::Present,
        ];
    }
}
