<?php

namespace Modules\ApprovalWorkflow\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\ApprovalWorkflow\Models\ApprovalWorkflow;

/**
 * @extends Factory<ApprovalWorkflow>
 */
class ApprovalWorkflowFactory extends Factory
{
    protected $model = ApprovalWorkflow::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(3, true),
            'workflowable_type' => 'approval_demo_item',
            'conditions' => null,
            'is_active' => true,
            'description' => fake()->sentence(),
        ];
    }
}
