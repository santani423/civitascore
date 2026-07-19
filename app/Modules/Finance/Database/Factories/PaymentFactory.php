<?php

namespace Modules\Finance\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Finance\Models\Invoice;
use Modules\Finance\Models\Payment;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'invoice_id' => Invoice::factory(),
            'amount' => fake()->randomElement([2_500_000, 5_000_000, 7_500_000, 10_000_000]),
            'paid_at' => fake()->dateTimeBetween('-9 months', 'now'),
            'method' => fake()->randomElement(['transfer', 'virtual_account', 'cash']),
        ];
    }
}
