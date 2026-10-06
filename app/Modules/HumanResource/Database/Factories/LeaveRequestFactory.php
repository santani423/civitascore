<?php

namespace Modules\HumanResource\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Academic\Models\Employee;
use Modules\HumanResource\Enums\LeaveStatus;
use Modules\HumanResource\Enums\LeaveType;
use Modules\HumanResource\Models\LeaveRequest;

/**
 * @extends Factory<LeaveRequest>
 */
class LeaveRequestFactory extends Factory
{
    protected $model = LeaveRequest::class;

    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'leave_type' => LeaveType::Annual,
            'start_date' => now()->addWeek()->toDateString(),
            'end_date' => now()->addWeek()->addDays(2)->toDateString(),
            'days' => 3,
            'reason' => 'Keperluan keluarga.',
            'status' => LeaveStatus::Draft,
        ];
    }
}
