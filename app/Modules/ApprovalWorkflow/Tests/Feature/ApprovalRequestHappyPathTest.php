<?php

use App\Models\User;
use Modules\ApprovalWorkflow\Enums\ApprovalApproverType;
use Modules\ApprovalWorkflow\Enums\ApprovalRequestStatus;
use Modules\ApprovalWorkflow\Enums\ApprovalRequestStepStatus;
use Modules\ApprovalWorkflow\Models\ApprovalWorkflow;
use Modules\ApprovalWorkflow\Services\ApprovalRequestService;
use Modules\ApprovalWorkflow\Tests\Fixtures\ApprovalDemoItem;
use Modules\UserManagement\Models\Role;

test('a two-step approval request is approved end-to-end through both steps', function () {
    $roleApprover = User::factory()->create();
    $role = Role::factory()->create();
    $roleApprover->roles()->attach($role->id, ['assigned_at' => now()]);

    $userApprover = User::factory()->create();

    $workflow = ApprovalWorkflow::factory()->create();
    $workflow->steps()->create([
        'sequence' => 1,
        'name' => 'Persetujuan Peran',
        'approver_type' => ApprovalApproverType::Role,
        'approver_role_id' => $role->id,
    ]);
    $workflow->steps()->create([
        'sequence' => 2,
        'name' => 'Persetujuan Pengguna',
        'approver_type' => ApprovalApproverType::User,
        'approver_user_id' => $userApprover->id,
    ]);

    $requester = User::factory()->create();
    $demoItem = ApprovalDemoItem::factory()->create(['created_by' => $requester->id]);

    $request = app(ApprovalRequestService::class)->submit($demoItem, $workflow->load('steps'), $requester);

    expect($request->status)->toBe(ApprovalRequestStatus::InProgress);
    $step1 = $request->steps()->where('sequence', 1)->first();
    expect($step1->status)->toBe(ApprovalRequestStepStatus::InReview);

    $this->actingAs($roleApprover);
    $this->postJson("/api/v1/approval-request-steps/{$step1->id}/approve", ['comment' => 'Sesuai'])
        ->assertApiSuccess();

    $request->refresh();
    expect($request->status)->toBe(ApprovalRequestStatus::InProgress);
    $step2 = $request->steps()->where('sequence', 2)->first();
    expect($step2->status)->toBe(ApprovalRequestStepStatus::InReview);

    $this->actingAs($userApprover);
    $this->postJson("/api/v1/approval-request-steps/{$step2->id}/approve")
        ->assertApiSuccess();

    $request->refresh();
    expect($request->status)->toBe(ApprovalRequestStatus::Approved);
    expect($request->completed_at)->not->toBeNull();

    $historyEvents = $request->histories()->pluck('event')->map(fn ($event) => $event->value)->all();
    expect($historyEvents)->toContain('submitted', 'step_advanced', 'approved');

    expect($requester->notifications()->count())->toBeGreaterThan(0);
});
