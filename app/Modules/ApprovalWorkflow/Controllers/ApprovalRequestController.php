<?php

namespace Modules\ApprovalWorkflow\Controllers;

use App\Http\Controllers\Controller;
use App\Support\Http\ApiResponse;
use App\Support\Http\ListQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\ApprovalWorkflow\Models\ApprovalRequest;
use Modules\ApprovalWorkflow\Resources\ApprovalRequestResource;
use Modules\ApprovalWorkflow\Services\ApprovalActionService;

class ApprovalRequestController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', ApprovalRequest::class);

        $query = ApprovalRequest::query();

        if (! $request->user()->hasPermissionTo('approval_requests.read')) {
            $query->where('requested_by', $request->user()->id);
        }

        $paginator = ListQuery::paginate(
            query: $query,
            request: $request,
            filterable: ['status', 'requestable_type'],
            sortable: ['created_at'],
        );

        return ApiResponse::paginated(ApprovalRequestResource::collection($paginator));
    }

    public function resubmit(Request $request, ApprovalRequest $approvalRequest, ApprovalActionService $actions): JsonResponse
    {
        $this->authorize('resubmit', $approvalRequest);

        $approvalRequest = $actions->resubmit($approvalRequest, $request->user());

        return ApiResponse::success(new ApprovalRequestResource($approvalRequest), 'Pengajuan berhasil diajukan ulang.');
    }

    public function show(ApprovalRequest $approvalRequest): JsonResponse
    {
        $this->authorize('view', $approvalRequest);

        return ApiResponse::success(new ApprovalRequestResource(
            $approvalRequest->load(['histories', 'currentStep.workflowStep.approverRole']),
        ));
    }
}
