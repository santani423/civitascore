<?php

namespace Modules\ApprovalWorkflow\Contracts;

use Modules\ApprovalWorkflow\Models\ApprovalRequest;
use Modules\ApprovalWorkflow\Models\ApprovalWorkflowStep;

/**
 * Default saat tidak ada modul yang menyediakan data jabatan/atasan.
 */
class NullContextualApproverResolver implements ContextualApproverResolver
{
    public function resolveUserIds(ApprovalWorkflowStep $step, ApprovalRequest $request): array
    {
        return [];
    }
}
