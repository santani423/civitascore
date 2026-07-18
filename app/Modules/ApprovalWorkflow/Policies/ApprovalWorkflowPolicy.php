<?php

namespace Modules\ApprovalWorkflow\Policies;

use App\Models\User;
use Modules\ApprovalWorkflow\Models\ApprovalWorkflow;

class ApprovalWorkflowPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('approval_workflows.read');
    }

    public function view(User $user, ApprovalWorkflow $workflow): bool
    {
        return $user->hasPermissionTo('approval_workflows.read');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('approval_workflows.create');
    }

    public function delete(User $user): bool
    {
        return $user->hasPermissionTo('approval_workflows.delete');
    }
}
