<?php

namespace Modules\ApprovalWorkflow\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Modules\ApprovalWorkflow\Models\ApprovalRequestStep;
use Modules\ApprovalWorkflow\Requests\ApproveStepRequest;
use Modules\ApprovalWorkflow\Requests\DelegateStepRequest;
use Modules\ApprovalWorkflow\Requests\RejectStepRequest;
use Modules\ApprovalWorkflow\Resources\ApprovalRequestResource;
use Modules\ApprovalWorkflow\Services\ApprovalActionService;

class ApprovalRequestStepController extends Controller
{
    public function __construct(private readonly ApprovalActionService $actions) {}

    public function approve(ApproveStepRequest $request, ApprovalRequestStep $approvalRequestStep): JsonResponse
    {
        $this->authorize('act', $approvalRequestStep);

        $approvalRequest = $this->actions->approve($approvalRequestStep, $request->user(), $request->validated('comment'));

        return ApiResponse::success(new ApprovalRequestResource($approvalRequest), 'Langkah berhasil disetujui.');
    }

    public function reject(RejectStepRequest $request, ApprovalRequestStep $approvalRequestStep): JsonResponse
    {
        $this->authorize('act', $approvalRequestStep);

        $approvalRequest = $this->actions->reject($approvalRequestStep, $request->user(), $request->validated('comment'));

        return ApiResponse::success(new ApprovalRequestResource($approvalRequest), 'Langkah berhasil ditolak.');
    }

    public function delegate(DelegateStepRequest $request, ApprovalRequestStep $approvalRequestStep): JsonResponse
    {
        $this->authorize('delegate', $approvalRequestStep);

        $delegateTo = User::query()->where('id', $request->validated('delegate_to'))->firstOrFail();

        $approvalRequest = $this->actions->delegate(
            $approvalRequestStep,
            $request->user(),
            $delegateTo,
            $request->validated('comment'),
        );

        return ApiResponse::success(new ApprovalRequestResource($approvalRequest), 'Langkah berhasil didelegasikan.');
    }
}
