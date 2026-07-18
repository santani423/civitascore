<?php

use Illuminate\Support\Facades\Route;
use Modules\ApprovalWorkflow\Controllers\ApprovalRequestController;
use Modules\ApprovalWorkflow\Controllers\ApprovalRequestStepController;
use Modules\ApprovalWorkflow\Controllers\ApprovalWorkflowController;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('approval-workflows', [ApprovalWorkflowController::class, 'index'])->middleware('permission:approval_workflows.read');
    Route::post('approval-workflows', [ApprovalWorkflowController::class, 'store'])->middleware('permission:approval_workflows.create');
    Route::get('approval-workflows/{approvalWorkflow}', [ApprovalWorkflowController::class, 'show'])->middleware('permission:approval_workflows.read');
    Route::delete('approval-workflows/{approvalWorkflow}', [ApprovalWorkflowController::class, 'destroy'])->middleware('permission:approval_workflows.delete');

    Route::get('approval-requests', [ApprovalRequestController::class, 'index']);
    Route::get('approval-requests/{approvalRequest}', [ApprovalRequestController::class, 'show']);

    Route::post('approval-request-steps/{approvalRequestStep}/approve', [ApprovalRequestStepController::class, 'approve']);
    Route::post('approval-request-steps/{approvalRequestStep}/reject', [ApprovalRequestStepController::class, 'reject']);
    Route::post('approval-request-steps/{approvalRequestStep}/delegate', [ApprovalRequestStepController::class, 'delegate']);
});
