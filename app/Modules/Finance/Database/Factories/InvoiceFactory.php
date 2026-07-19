<?php

namespace Modules\Finance\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Academic\Models\Student;
use Modules\Finance\Enums\InvoiceStatus;
use Modules\Finance\Models\Invoice;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition(): array
    {
        $amount = fake()->randomElement([2_500_000, 5_000_000, 7_500_000, 10_000_000]);

        return [
            'student_id' => Student::factory(),
            'period' => '2026/2027 Ganjil',
            'amount' => $amount,
            'paid_amount' => $amount,
            'status' => InvoiceStatus::Paid,
            'due_date' => now()->addMonth(),
        ];
    }
}
