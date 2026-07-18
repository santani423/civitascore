<?php

namespace Modules\ApprovalWorkflow\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\ApprovalWorkflow\Enums\ApprovalRequestStepStatus;
use Modules\ApprovalWorkflow\Models\ApprovalRequest;
use Modules\ApprovalWorkflow\Models\ApprovalRequestStep;
use Modules\ApprovalWorkflow\Models\ApprovalWorkflowStep;

/**
 * @extends Factory<ApprovalRequestStep>
 */
class ApprovalRequestStepFactory extends Factory
{
    protected $model = ApprovalRequestStep::class;

    public function definition(): array
    {
        return [
            'approval_request_id' => ApprovalRequest::factory(),
            'approval_workflow_step_id' => ApprovalWorkflowStep::factory(),
            'sequence' => 1,
            'assigned_approver_user_id' => null,
            'status' => ApprovalRequestStepStatus::Pending,
            'acted_at' => null,
        ];
    }
}
