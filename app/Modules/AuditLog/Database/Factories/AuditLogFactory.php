<?php

namespace Modules\AuditLog\Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\AuditLog\Enums\AuditAction;
use Modules\AuditLog\Models\AuditLog;

/**
 * @extends Factory<AuditLog>
 */
class AuditLogFactory extends Factory
{
    protected $model = AuditLog::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'auditable_type' => User::class,
            'auditable_id' => (string) Str::ulid(),
            'action' => fake()->randomElement(AuditAction::cases()),
            'old_values' => null,
            'new_values' => ['name' => fake()->name()],
            'reason' => null,
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
        ];
    }
}
