<?php

namespace Modules\ApprovalWorkflow\Policies;

use App\Models\User;
use Modules\ApprovalWorkflow\Enums\ApprovalApproverType;
use Modules\ApprovalWorkflow\Models\ApprovalRequestStep;
use Modules\ApprovalWorkflow\Support\ApproverResolver;
use Modules\SystemSetting\Services\FeatureFlagService;

class ApprovalRequestStepPolicy
{
    /**
     * The actor must be the pre-assigned approver (User-type step), or
     * currently hold the step's role (Role-type step, first to act wins —
     * enforced by the step's status guard in ApprovalActionService, not
     * here).
     *
     * Contextual steps (jabatan/atasan/kepala unit): once a single approver
     * is pinned — resolved at submission, or set by delegation — only that
     * user may act; otherwise any user the ContextualApproverResolver
     * currently returns may act.
     */
    public function act(User $user, ApprovalRequestStep $step): bool
    {
        $workflowStep = $step->workflowStep;

        if ($workflowStep->approver_type->isContextual()) {
            if ($step->assigned_approver_user_id !== null) {
                return $user->id === $step->assigned_approver_user_id;
            }

            $request = $step->request;

            return $request !== null
                && in_array($user->id, app(ApproverResolver::class)->contextualUserIds($workflowStep, $request), true);
        }

        return match ($workflowStep->approver_type) {
            ApprovalApproverType::User => $user->id === $step->assigned_approver_user_id,
            ApprovalApproverType::Role => $workflowStep->approverRole !== null && $user->hasRole($workflowStep->approverRole->slug),
            default => false,
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
