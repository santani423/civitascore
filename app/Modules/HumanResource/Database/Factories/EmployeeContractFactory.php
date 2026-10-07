<?php

namespace Modules\HumanResource\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Academic\Models\Employee;
use Modules\HumanResource\Enums\ContractStatus;
use Modules\HumanResource\Enums\ContractType;
use Modules\HumanResource\Models\EmployeeContract;

/**
 * @extends Factory<EmployeeContract>
 */
class EmployeeContractFactory extends Factory
{
    protected $model = EmployeeContract::class;

    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'contract_number' => strtoupper(fake()->unique()->bothify('PKWT/####/??')),
            'contract_type' => ContractType::Pkwt,
            'start_date' => now()->subYear()->toDateString(),
            'end_date' => now()->addMonths(6)->toDateString(),
            'status' => ContractStatus::Active,
        ];
    }
}
