<?php

namespace Modules\ApprovalWorkflow\Policies;

use App\Models\User;
use Modules\ApprovalWorkflow\Enums\ApprovalApproverType;
use Modules\ApprovalWorkflow\Models\ApprovalRequestStep;
use Modules\SystemSetting\Services\FeatureFlagService;

class ApprovalRequestStepPolicy
{
    /**
     * The actor must be the pre-assigned approver (User-type step), or
     * currently hold the step's role (Role-type step, first to act wins —
     * enforced by the step's status guard in ApprovalActionService, not
     * here). Position-type steps are unsupported in Phase 1 and always
     * deny.
     */
    public function act(User $user, ApprovalRequestStep $step): bool
    {
        $workflowStep = $step->workflowStep;

        return match ($workflowStep->approver_type) {
            ApprovalApproverType::User => $user->id === $step->assigned_approver_user_id,
            ApprovalApproverType::Role => $workflowStep->approverRole !== null && $user->hasRole($workflowStep->approverRole->slug),
            ApprovalApproverType::Position => false,
        };
    }

    public function delegate(User $user, ApprovalRequestStep $step): bool
    {
        if (! app(FeatureFlagService::class)->isEnabled('approval_workflow.delegation_enabled')) {
            return false;
        }

        return $this->act($user, $step);
    }
}
