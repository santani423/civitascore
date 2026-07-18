<?php

use App\Models\User;
use Modules\ApprovalWorkflow\Enums\ApprovalApproverType;
use Modules\ApprovalWorkflow\Enums\ApprovalRejectAction;
use Modules\ApprovalWorkflow\Enums\ApprovalRequestStatus;
use Modules\ApprovalWorkflow\Enums\ApprovalRequestStepStatus;
use Modules\ApprovalWorkflow\Models\ApprovalWorkflow;
use Modules\ApprovalWorkflow\Services\ApprovalRequestService;
use Modules\ApprovalWorkflow\Tests\Fixtures\ApprovalDemoItem;

test('rejecting step 2 with ReturnToPreviousStep sends the request back to step 1', function () {
    $step1Approver = User::factory()->create();
    $step2Approver = User::factory()->create();

    $workflow = ApprovalWorkflow::factory()->create();
    $workflow->steps()->create([
        'sequence' => 1,
        'name' => 'Verifikasi',
        'approver_type' => ApprovalApproverType::User,
        'approver_user_id' => $step1Approver->id,
    ]);
    $workflow->steps()->create([
        'sequence' => 2,
        'name' => 'Persetujuan Akhir',
        'approver_type' => ApprovalApproverType::User,
        'approver_user_id' => $step2Approver->id,
        'action_on_reject' => ApprovalRejectAction::ReturnToPreviousStep,
    ]);

    $requester = User::factory()->create();
    $demoItem = ApprovalDemoItem::factory()->create(['created_by' => $requester->id]);
    $request = app(ApprovalRequestService::class)->submit($demoItem, $workflow->load('steps'), $requester);

    $step1 = $request->steps()->where('sequence', 1)->first();
    $this->actingAs($step1Approver);
    $this->postJson("/api/v1/approval-request-steps/{$step1->id}/approve")->assertApiSuccess();

    $step2 = $request->refresh()->steps()->where('sequence', 2)->first();
    $this->actingAs($step2Approver);
    $this->postJson("/api/v1/approval-request-steps/{$step2->id}/reject", ['comment' => 'Perlu revisi'])
        ->assertApiSuccess();

    $request->refresh();
    expect($request->status)->toBe(ApprovalRequestStatus::InProgress);
    expect($request->current_step_id)->toBe($step1->id);
    expect($step1->refresh()->status)->toBe(ApprovalRequestStepStatus::Pending);
    expect($step2->refresh()->status)->toBe(ApprovalRequestStepStatus::Skipped);
});

test('rejecting the first step with ReturnToPreviousStep falls back to StopWorkflow (nowhere to return to)', function () {
    $approver = User::factory()->create();

    $workflow = ApprovalWorkflow::factory()->create();
    $workflow->steps()->create([
        'sequence' => 1,
        'name' => 'Satu-satunya Langkah',
        'approver_type' => ApprovalApproverType::User,
        'approver_user_id' => $approver->id,
        'action_on_reject' => ApprovalRejectAction::ReturnToPreviousStep,
    ]);

    $requester = User::factory()->create();
    $demoItem = ApprovalDemoItem::factory()->create(['created_by' => $requester->id]);
    $request = app(ApprovalRequestService::class)->submit($demoItem, $workflow->load('steps'), $requester);

    $this->actingAs($approver);
    $this->postJson("/api/v1/approval-request-steps/{$request->currentStep->id}/reject", ['comment' => 'Ditolak'])
        ->assertApiSuccess();

    $request->refresh();
    expect($request->status)->toBe(ApprovalRequestStatus::Rejected);
});
