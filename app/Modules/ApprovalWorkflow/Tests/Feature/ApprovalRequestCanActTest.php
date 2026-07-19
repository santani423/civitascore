<?php

use App\Models\User;
use Modules\ApprovalWorkflow\Enums\ApprovalApproverType;
use Modules\ApprovalWorkflow\Models\ApprovalWorkflow;
use Modules\ApprovalWorkflow\Services\ApprovalRequestService;
use Modules\ApprovalWorkflow\Tests\Fixtures\ApprovalDemoItem;

/**
 * ApprovalRequestResource::can_act must match ApprovalRequestStepPolicy::act()
 * exactly (it's a direct reuse, not a re-implementation) — these tests
 * exercise it through the real GET /approval-requests/{id} endpoint so a
 * regression here would also break the actual Approve/Reject buttons.
 */
function buildInProgressRequest(User $approver, User $requester): string
{
    $workflow = ApprovalWorkflow::factory()->create();
    $workflow->steps()->create([
        'sequence' => 1,
        'name' => 'Persetujuan',
        'approver_type' => ApprovalApproverType::User,
        'approver_user_id' => $approver->id,
    ]);

    $demoItem = ApprovalDemoItem::factory()->create(['created_by' => $requester->id]);
    $request = app(ApprovalRequestService::class)->submit($demoItem, $workflow->load('steps'), $requester);

    return $request->id;
}

test('the assigned approver sees can_act true on the request they must act on', function () {
    $approver = User::factory()->create();
    $requester = User::factory()->create();
    $requestId = buildInProgressRequest($approver, $requester);

    $response = $this->actingAs($approver)->getJson("/api/v1/approval-requests/{$requestId}")->assertApiSuccess();

    expect($response->json('data.can_act'))->toBeTrue();
});

test('the requester can view their own request but can_act is false — they are not the approver', function () {
    $approver = User::factory()->create();
    $requester = User::factory()->create();
    $requestId = buildInProgressRequest($approver, $requester);

    $asRequester = $this->actingAs($requester)->getJson("/api/v1/approval-requests/{$requestId}")->assertApiSuccess();
    expect($asRequester->json('data.can_act'))->toBeFalse();
});

test('a bystander with no stake in the request cannot even view it', function () {
    $approver = User::factory()->create();
    $requester = User::factory()->create();
    $bystander = User::factory()->create();
    $requestId = buildInProgressRequest($approver, $requester);

    $this->actingAs($bystander)->getJson("/api/v1/approval-requests/{$requestId}")->assertApiError(403);
});

test('can_act turns false again for the approver once the request is fully approved', function () {
    $approver = User::factory()->create();
    $requester = User::factory()->create();
    $requestId = buildInProgressRequest($approver, $requester);

    $step = $this->actingAs($approver)
        ->getJson("/api/v1/approval-requests/{$requestId}")
        ->json('data');
    expect($step['can_act'])->toBeTrue();

    $currentStepId = \Modules\ApprovalWorkflow\Models\ApprovalRequest::query()->find($requestId)->current_step_id;
    $this->actingAs($approver)->postJson("/api/v1/approval-request-steps/{$currentStepId}/approve")->assertApiSuccess();

    // The request is now fully Approved (current_step_id is null), so the
    // approver themself no longer passes ApprovalRequestPolicy::view() —
    // check via a tenant-wide reader instead, which can still view it.
    actingAsUserWithPermissions(['approval_requests.read']);
    $response = $this->getJson("/api/v1/approval-requests/{$requestId}")->assertApiSuccess();
    expect($response->json('data.can_act'))->toBeFalse();
});
