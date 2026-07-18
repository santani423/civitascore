<?php

use App\Models\User;
use Modules\ApprovalWorkflow\Enums\ApprovalApproverType;
use Modules\ApprovalWorkflow\Enums\ApprovalRejectAction;
use Modules\ApprovalWorkflow\Enums\ApprovalRequestStatus;
use Modules\ApprovalWorkflow\Models\ApprovalRequest;
use Modules\ApprovalWorkflow\Models\ApprovalWorkflow;
use Modules\ApprovalWorkflow\Services\ApprovalRequestService;
use Modules\ApprovalWorkflow\Tests\Fixtures\ApprovalDemoItem;

function makeSingleStepDemoRequest(User $approver, ApprovalRejectAction $rejectAction = ApprovalRejectAction::StopWorkflow): ApprovalRequest
{
    $workflow = ApprovalWorkflow::factory()->create();
    $workflow->steps()->create([
        'sequence' => 1,
        'name' => 'Tinjauan',
        'approver_type' => ApprovalApproverType::User,
        'approver_user_id' => $approver->id,
        'action_on_reject' => $rejectAction,
    ]);

    $requester = User::factory()->create();
    $demoItem = ApprovalDemoItem::factory()->create(['created_by' => $requester->id]);

    return app(ApprovalRequestService::class)->submit($demoItem, $workflow->load('steps'), $requester);
}

test('rejecting the only step with StopWorkflow immediately rejects the request', function () {
    $approver = User::factory()->create();
    $request = makeSingleStepDemoRequest($approver);
    $step = $request->currentStep;

    $this->actingAs($approver);
    $response = $this->postJson("/api/v1/approval-request-steps/{$step->id}/reject", ['comment' => 'Dokumen tidak lengkap']);

    $response->assertApiSuccess();
    $request->refresh();
    expect($request->status)->toBe(ApprovalRequestStatus::Rejected);
    expect($request->completed_at)->not->toBeNull();
});

test('rejecting without a comment fails validation', function () {
    $approver = User::factory()->create();
    $request = makeSingleStepDemoRequest($approver);
    $step = $request->currentStep;

    $this->actingAs($approver);
    $response = $this->postJson("/api/v1/approval-request-steps/{$step->id}/reject", []);

    $response->assertApiError(422);
});

test('acting twice on an already-decided step is a conflict', function () {
    $approver = User::factory()->create();
    $request = makeSingleStepDemoRequest($approver);
    $step = $request->currentStep;

    $this->actingAs($approver);
    $this->postJson("/api/v1/approval-request-steps/{$step->id}/approve")->assertApiSuccess();

    $response = $this->postJson("/api/v1/approval-request-steps/{$step->id}/approve");

    $response->assertApiError(409);
});

test('an actor who is not the assigned approver is forbidden from acting', function () {
    $approver = User::factory()->create();
    $request = makeSingleStepDemoRequest($approver);
    $step = $request->currentStep;

    $intruder = User::factory()->create();
    $this->actingAs($intruder);

    $response = $this->postJson("/api/v1/approval-request-steps/{$step->id}/approve");

    $response->assertApiError(403);
});
