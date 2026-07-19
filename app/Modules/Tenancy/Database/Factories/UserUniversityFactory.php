<?php

namespace Modules\Tenancy\Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Tenancy\Enums\MembershipStatus;
use Modules\Tenancy\Enums\MembershipType;
use Modules\Tenancy\Models\University;
use Modules\Tenancy\Models\UserUniversity;

/**
 * @extends Factory<UserUniversity>
 */
class UserUniversityFactory extends Factory
{
    protected $model = UserUniversity::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'university_id' => University::factory(),
            'membership_type' => MembershipType::Staff,
            'status' => MembershipStatus::Active,
            'joined_at' => now(),
            'is_default' => true,
        ];
    }
}
