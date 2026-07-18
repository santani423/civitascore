<?php

namespace Modules\ApprovalWorkflow\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\ApprovalWorkflow\Enums\ApprovalApproverType;
use Modules\ApprovalWorkflow\Enums\ApprovalRejectAction;
use Modules\ApprovalWorkflow\Models\ApprovalWorkflow;
use Modules\ApprovalWorkflow\Models\ApprovalWorkflowStep;

/**
 * @extends Factory<ApprovalWorkflowStep>
 */
class ApprovalWorkflowStepFactory extends Factory
{
    protected $model = ApprovalWorkflowStep::class;

    public function definition(): array
    {
        return [
            'approval_workflow_id' => ApprovalWorkflow::factory(),
            'sequence' => 1,
            'name' => fake()->jobTitle(),
            'approver_type' => ApprovalApproverType::User,
            'approver_role_id' => null,
            'approver_user_id' => null,
            'action_on_reject' => ApprovalRejectAction::StopWorkflow,
        ];
    }
}
