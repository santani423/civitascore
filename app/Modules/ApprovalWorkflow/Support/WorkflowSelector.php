<?php

namespace Modules\ApprovalWorkflow\Support;

use Modules\ApprovalWorkflow\Models\ApprovalWorkflow;

/**
 * Picks the active workflow template for a workflowable_type whose
 * `conditions` (an open key => value rule-bag) are satisfied by the given
 * context — e.g. later {"faculty_slug": "ilmu-komputer"}. A workflow with
 * no conditions matches any context.
 */
class WorkflowSelector
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function selectFor(string $workflowableType, array $context = []): ?ApprovalWorkflow
    {
        return ApprovalWorkflow::query()
            ->where('workflowable_type', $workflowableType)
            ->where('is_active', true)
            ->get()
            ->first(fn (ApprovalWorkflow $workflow): bool => $this->matches($workflow->conditions, $context));
    }

    /**
     * @param  array<string, mixed>|null  $conditions
     * @param  array<string, mixed>  $context
     */
    private function matches(?array $conditions, array $context): bool
    {
        if ($conditions === null || $conditions === []) {
            return true;
        }

        foreach ($conditions as $key => $expected) {
            if (! array_key_exists($key, $context) || $context[$key] !== $expected) {
                return false;
            }
        }

        return true;
    }
}
