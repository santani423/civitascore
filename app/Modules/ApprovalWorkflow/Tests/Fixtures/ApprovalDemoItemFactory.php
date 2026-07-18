<?php

namespace Modules\ApprovalWorkflow\Tests\Fixtures;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ApprovalDemoItem>
 */
class ApprovalDemoItemFactory extends Factory
{
    protected $model = ApprovalDemoItem::class;

    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'created_by' => User::factory(),
        ];
    }
}
