<?php

use App\Models\User;
use Modules\ApprovalWorkflow\Enums\ApprovalApproverType;
use Modules\ApprovalWorkflow\Models\ApprovalRequest;
use Modules\ApprovalWorkflow\Models\ApprovalWorkflow;
use Modules\ApprovalWorkflow\Services\ApprovalRequestService;
use Modules\ApprovalWorkflow\Tests\Fixtures\ApprovalDemoItem;
use Modules\SystemSetting\Models\FeatureFlag;

function submitSingleStepDemoRequestTo(User $approver): ApprovalRequest
{
    $workflow = ApprovalWorkflow::factory()->create();
    $workflow->steps()->create([
        'sequence' => 1,
        'name' => 'Tinjauan',
        'approver_type' => ApprovalApproverType::User,
        'approver_user_id' => $approver->id,
    ]);

    $requester = User::factory()->create();
    $demoItem = ApprovalDemoItem::factory()->create(['created_by' => $requester->id]);

    return app(ApprovalRequestService::class)->submit($demoItem, $workflow->load('steps'), $requester);
}

test('the assigned approver can delegate the step to another user when the feature flag is enabled', function () {
    FeatureFlag::factory()->create(['key' => 'approval_workflow.delegation_enabled', 'is_enabled' => true]);

    $approver = User::factory()->create();
    $delegate = User::factory()->create();
    $request = submitSingleStepDemoRequestTo($approver);
    $step = $request->currentStep;

    $this->actingAs($approver);
    $response = $this->postJson("/api/v1/approval-request-steps/{$step->id}/delegate", [
        'delegate_to' => $delegate->id,
        'comment' => 'Saya sedang cuti',
    ]);

    $response->assertApiSuccess();
    expect($step->refresh()->assigned_approver_user_id)->toBe($delegate->id);

    $this->actingAs($delegate);
    $this->postJson("/api/v1/approval-request-steps/{$step->id}/approve")->assertApiSuccess();
});

test('delegation is forbidden when the feature flag is disabled', function () {
    FeatureFlag::factory()->create(['key' => 'approval_workflow.delegation_enabled', 'is_enabled' => false]);

    $approver = User::factory()->create();
    $delegate = User::factory()->create();
    $request = submitSingleStepDemoRequestTo($approver);

    $this->actingAs($approver);
    $response = $this->postJson("/api/v1/approval-request-steps/{$request->currentStep->id}/delegate", [
        'delegate_to' => $delegate->id,
    ]);

    $response->assertApiError(403);
});
