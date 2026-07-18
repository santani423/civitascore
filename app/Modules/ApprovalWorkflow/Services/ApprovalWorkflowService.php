<?php

namespace Modules\ApprovalWorkflow\Services;

use Illuminate\Support\Facades\DB;
use Modules\ApprovalWorkflow\Enums\ApprovalRejectAction;
use Modules\ApprovalWorkflow\Models\ApprovalWorkflow;

class ApprovalWorkflowService
{
    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, array<string, mixed>>  $steps
     */
    public function create(array $data, array $steps): ApprovalWorkflow
    {
        return DB::transaction(function () use ($data, $steps): ApprovalWorkflow {
            $workflow = ApprovalWorkflow::create($data);

            foreach ($steps as $index => $step) {
                $workflow->steps()->create([
                    'sequence' => $step['sequence'] ?? $index + 1,
                    'name' => $step['name'],
                    'approver_type' => $step['approver_type'],
                    'approver_role_id' => $step['approver_role_id'] ?? null,
                    'approver_user_id' => $step['approver_user_id'] ?? null,
                    'action_on_reject' => $step['action_on_reject'] ?? ApprovalRejectAction::StopWorkflow,
                ]);
            }

            return $workflow->load('steps');
        });
    }

    public function delete(ApprovalWorkflow $workflow): void
    {
        DB::transaction(fn () => $workflow->delete());
    }
}
