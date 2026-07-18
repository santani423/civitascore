<?php

namespace Modules\UserManagement\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\UserManagement\Enums\PermissionAction;
use Modules\UserManagement\Enums\PermissionScope;
use Modules\UserManagement\Models\Permission;

/**
 * @extends Factory<Permission>
 */
class PermissionFactory extends Factory
{
    protected $model = Permission::class;

    public function definition(): array
    {
        $resource = fake()->unique()->word();
        $action = fake()->randomElement(PermissionAction::cases());

        return [
            'name' => ucfirst($action->value)." {$resource}",
            'slug' => Permission::slugFor($resource, $action),
            'scope' => PermissionScope::Data,
            'action' => $action,
            'resource' => $resource,
            'description' => fake()->sentence(),
            'is_system' => false,
        ];
    }
}
