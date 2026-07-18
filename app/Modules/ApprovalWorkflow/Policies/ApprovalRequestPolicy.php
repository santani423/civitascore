<?php

namespace Modules\ApprovalWorkflow\Policies;

use App\Models\User;
use Modules\ApprovalWorkflow\Models\ApprovalRequest;

class ApprovalRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, ApprovalRequest $request): bool
    {
        return $user->id === $request->requested_by
            || $user->id === $request->currentStep?->assigned_approver_user_id
            || $user->hasPermissionTo('approval_requests.read');
    }
}
