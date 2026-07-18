<?php

namespace Modules\UserManagement\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\UserManagement\Models\Role;

/**
 * @extends Factory<Role>
 */
class RoleFactory extends Factory
{
    protected $model = Role::class;

    public function definition(): array
    {
        $name = fake()->unique()->jobTitle();

        return [
            'name' => $name,
            'slug' => Str::slug($name, '_'),
            'description' => fake()->sentence(),
            'is_system' => false,
        ];
    }
}
