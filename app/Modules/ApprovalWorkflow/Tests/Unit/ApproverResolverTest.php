<?php

use App\Models\User;
use Modules\ApprovalWorkflow\Enums\ApprovalApproverType;
use Modules\ApprovalWorkflow\Models\ApprovalWorkflowStep;
use Modules\ApprovalWorkflow\Support\ApproverResolver;
use Modules\UserManagement\Models\Role;

test('a User-type step resolves immediately to its configured approver_user_id', function () {
    $approver = User::factory()->create();
    $step = ApprovalWorkflowStep::factory()->create([
        'approver_type' => ApprovalApproverType::User,
        'approver_user_id' => $approver->id,
    ]);

    $resolved = (new ApproverResolver)->resolveAssignedUserId($step);

    expect($resolved)->toBe($approver->id);
});

test('a Role-type step deliberately resolves to null (checked at authorization time instead)', function () {
    $role = Role::factory()->create();
    $step = ApprovalWorkflowStep::factory()->create([
        'approver_type' => ApprovalApproverType::Role,
        'approver_role_id' => $role->id,
        'approver_user_id' => null,
    ]);

    $resolved = (new ApproverResolver)->resolveAssignedUserId($step);

    expect($resolved)->toBeNull();
});

test('a Position-type step throws, since it is unsupported in Phase 1', function () {
    $step = ApprovalWorkflowStep::factory()->create(['approver_type' => ApprovalApproverType::Position]);

    (new ApproverResolver)->resolveAssignedUserId($step);
})->throws(LogicException::class);
