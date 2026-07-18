<?php

namespace Modules\ApprovalWorkflow\Support;

use LogicException;
use Modules\ApprovalWorkflow\Enums\ApprovalApproverType;
use Modules\ApprovalWorkflow\Models\ApprovalWorkflowStep;

/**
 * Resolves the user_id to pre-assign to a request step at creation time.
 * User-type steps resolve immediately (already known). Role-type steps
 * deliberately resolve to null — "any user holding this role may act" is
 * checked at authorization time instead of picking one arbitrary user now,
 * since role membership can change between submission and a step becoming
 * current.
 */
class ApproverResolver
{
    public function resolveAssignedUserId(ApprovalWorkflowStep $step): ?string
    {
        return match ($step->approver_type) {
            ApprovalApproverType::User => $step->approver_user_id,
            ApprovalApproverType::Role => null,
            ApprovalApproverType::Position => throw new LogicException(
                'Approver berbasis jabatan (Position) belum didukung pada Fase 1 — akan diimplementasikan bersama modul Kepegawaian.',
            ),
        };
    }
}
