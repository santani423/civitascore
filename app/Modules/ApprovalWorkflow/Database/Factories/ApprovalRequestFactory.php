<?php

namespace Modules\ApprovalWorkflow\Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\ApprovalWorkflow\Enums\ApprovalRequestStatus;
use Modules\ApprovalWorkflow\Models\ApprovalRequest;
use Modules\ApprovalWorkflow\Models\ApprovalWorkflow;
use Modules\ApprovalWorkflow\Tests\Fixtures\ApprovalDemoItem;

/**
 * @extends Factory<ApprovalRequest>
 */
class ApprovalRequestFactory extends Factory
{
    protected $model = ApprovalRequest::class;

    public function definition(): array
    {
        return [
            'approval_workflow_id' => ApprovalWorkflow::factory(),
            'requestable_type' => ApprovalDemoItem::class,
            'requestable_id' => ApprovalDemoItem::factory(),
            'requested_by' => User::factory(),
            'current_step_id' => null,
            'status' => ApprovalRequestStatus::Submitted,
            'submitted_at' => now(),
            'completed_at' => null,
            'notes' => null,
        ];
    }
}
