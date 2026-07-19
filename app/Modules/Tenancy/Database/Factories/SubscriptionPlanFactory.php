<?php

namespace Modules\Tenancy\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Tenancy\Models\SubscriptionPlan;

/**
 * @extends Factory<SubscriptionPlan>
 */
class SubscriptionPlanFactory extends Factory
{
    protected $model = SubscriptionPlan::class;

    public function definition(): array
    {
        return [
            'code' => fake()->unique()->lexify('plan_????'),
            'name' => fake()->words(2, true),
            'limits' => ['max_users' => 100, 'max_storage_gb' => 10],
            'price' => fake()->randomFloat(2, 0, 10_000_000),
            'billing_period' => 'monthly',
            'is_active' => true,
        ];
    }
}
