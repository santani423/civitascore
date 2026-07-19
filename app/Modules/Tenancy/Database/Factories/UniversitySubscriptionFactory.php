<?php

namespace Modules\Tenancy\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Tenancy\Enums\SubscriptionStatus;
use Modules\Tenancy\Models\SubscriptionPlan;
use Modules\Tenancy\Models\University;
use Modules\Tenancy\Models\UniversitySubscription;

/**
 * @extends Factory<UniversitySubscription>
 */
class UniversitySubscriptionFactory extends Factory
{
    protected $model = UniversitySubscription::class;

    public function definition(): array
    {
        return [
            'university_id' => University::factory(),
            'subscription_plan_id' => SubscriptionPlan::factory(),
            'status' => SubscriptionStatus::Active,
            'current_period_starts_at' => now(),
            'current_period_ends_at' => now()->addYear(),
        ];
    }
}
