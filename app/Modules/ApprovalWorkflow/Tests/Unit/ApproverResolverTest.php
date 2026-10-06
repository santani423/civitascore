<?php

use App\Models\User;
use Modules\ApprovalWorkflow\Contracts\ContextualApproverResolver;
use Modules\ApprovalWorkflow\Contracts\NullContextualApproverResolver;
use Modules\ApprovalWorkflow\Enums\ApprovalApproverType;
use Modules\ApprovalWorkflow\Models\ApprovalRequest;
use Modules\ApprovalWorkflow\Models\ApprovalWorkflowStep;
use Modules\ApprovalWorkflow\Support\ApproverResolver;
use Modules\UserManagement\Models\Role;

test('a User-type step resolves immediately to its configured approver_user_id', function () {
    $approver = User::factory()->create();
    $step = ApprovalWorkflowStep::factory()->create([
        'approver_type' => ApprovalApproverType::User,
        'approver_user_id' => $approver->id,
    ]);

    $resolved = (new ApproverResolver(new NullContextualApproverResolver))->resolveAssignedUserId($step);

    expect($resolved)->toBe($approver->id);
});

test('a Role-type step deliberately resolves to null (checked at authorization time instead)', function () {
    $role = Role::factory()->create();
    $step = ApprovalWorkflowStep::factory()->create([
        'approver_type' => ApprovalApproverType::Role,
        'approver_role_id' => $role->id,
        'approver_user_id' => null,
    ]);

    $resolved = (new ApproverResolver(new NullContextualApproverResolver))->resolveAssignedUserId($step);

    expect($resolved)->toBeNull();
});

test('a Position-type step no longer throws — without a contextual resolver nobody is assigned', function () {
    $step = ApprovalWorkflowStep::factory()->create(['approver_type' => ApprovalApproverType::Position]);

    $resolved = (new ApproverResolver(new NullContextualApproverResolver))->resolveAssignedUserId($step);

    expect($resolved)->toBeNull();
});

test('a contextual step is pinned to the single eligible user, never to the requester', function () {
    $requester = User::factory()->create();
    $holder = User::factory()->create();
    $step = ApprovalWorkflowStep::factory()->create(['approver_type' => ApprovalApproverType::Position]);
    $request = new ApprovalRequest(['requested_by' => $requester->id]);

    $contextual = new class([$requester->id, $holder->id]) implements ContextualApproverResolver
    {
        /** @param list<string> $ids */
        public function __construct(private array $ids) {}

        public function resolveUserIds(ApprovalWorkflowStep $step, ApprovalRequest $request): array
        {
            return $this->ids;
        }
    };

    $resolved = (new ApproverResolver($contextual))->resolveAssignedUserId($step, $request);

    expect($resolved)->toBe($holder->id);
});
