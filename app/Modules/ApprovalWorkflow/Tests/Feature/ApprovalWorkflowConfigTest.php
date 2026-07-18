<?php

use App\Models\User;
use Modules\ApprovalWorkflow\Enums\ApprovalApproverType;
use Modules\ApprovalWorkflow\Models\ApprovalWorkflow;

test('a permitted user can create a workflow template with steps', function () {
    actingAsUserWithPermissions(['approval_workflows.create', 'approval_workflows.read']);

    $approver = User::factory()->create();

    $response = $this->postJson('/api/v1/approval-workflows', [
        'name' => 'Demo Approval',
        'workflowable_type' => 'approval_demo_item',
        'steps' => [
            ['name' => 'Tinjauan', 'approver_type' => ApprovalApproverType::User->value, 'approver_user_id' => $approver->id],
        ],
    ]);

    $response->assertApiSuccess(201);
    expect($response->json('data.steps'))->toHaveCount(1);
});

test('creating a workflow without any steps fails validation', function () {
    actingAsUserWithPermissions(['approval_workflows.create']);

    $response = $this->postJson('/api/v1/approval-workflows', [
        'name' => 'Empty',
        'workflowable_type' => 'approval_demo_item',
        'steps' => [],
    ]);

    $response->assertApiError(422);
});

test('a permitted user can delete a workflow template', function () {
    actingAsUserWithPermissions(['approval_workflows.delete']);

    $workflow = ApprovalWorkflow::factory()->create();

    $response = $this->deleteJson("/api/v1/approval-workflows/{$workflow->id}");

    $response->assertApiSuccess();
    expect(ApprovalWorkflow::query()->find($workflow->id))->toBeNull();
});
