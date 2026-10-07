<?php

namespace Modules\ApprovalWorkflow\Support;

use Modules\ApprovalWorkflow\Contracts\ContextualApproverResolver;
use Modules\ApprovalWorkflow\Enums\ApprovalApproverType;
use Modules\ApprovalWorkflow\Models\ApprovalRequest;
use Modules\ApprovalWorkflow\Models\ApprovalWorkflowStep;

/**
 * Resolves the user_id to pre-assign to a request step at creation time.
 * User-type steps resolve immediately (already known). Role-type steps
 * deliberately resolve to null — "any user holding this role may act" is
 * checked at authorization time instead of picking one arbitrary user now,
 * since role membership can change between submission and a step becoming
 * current.
 *
 * Contextual steps (jabatan, atasan langsung, kepala unit) are resolved
 * through ContextualApproverResolver: exactly one eligible user → assigned
 * (so they get notified and it shows as "menunggu X"); several (mis. dua
 * pemegang jabatan yang sama) → null, and any of them may act, checked
 * again at authorization time like Role-type steps.
 */
class ApproverResolver
{
    public function __construct(private readonly ContextualApproverResolver $contextual) {}

    public function resolveAssignedUserId(ApprovalWorkflowStep $step, ?ApprovalRequest $request = null): ?string
    {
        if ($step->approver_type->isContextual()) {
            $userIds = $request !== null ? $this->contextualUserIds($step, $request) : [];

            return count($userIds) === 1 ? $userIds[0] : null;
        }

        return match ($step->approver_type) {
            ApprovalApproverType::User => $step->approver_user_id,
            default => null,
        };
    }

    /**
     * @return list<string>
     */
    public function contextualUserIds(ApprovalWorkflowStep $step, ApprovalRequest $request): array
    {
        return array_values(array_filter(
            $this->contextual->resolveUserIds($step, $request),
            fn (string $userId): bool => $userId !== $request->requested_by,
        ));
    }
}
