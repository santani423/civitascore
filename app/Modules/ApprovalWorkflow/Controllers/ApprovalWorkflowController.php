<?php

namespace Modules\ApprovalWorkflow\Controllers;

use App\Http\Controllers\Controller;
use App\Support\Http\ApiResponse;
use App\Support\Http\ListQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\ApprovalWorkflow\Models\ApprovalWorkflow;
use Modules\ApprovalWorkflow\Requests\StoreApprovalWorkflowRequest;
use Modules\ApprovalWorkflow\Resources\ApprovalWorkflowResource;
use Modules\ApprovalWorkflow\Services\ApprovalWorkflowService;

class ApprovalWorkflowController extends Controller
{
    public function __construct(private readonly ApprovalWorkflowService $workflows) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', ApprovalWorkflow::class);

        $paginator = ListQuery::paginate(
            query: ApprovalWorkflow::query()->with('steps'),
            request: $request,
            searchable: ['name', 'workflowable_type'],
            filterable: ['workflowable_type', 'is_active'],
            sortable: ['name', 'created_at'],
        );

        return ApiResponse::paginated(ApprovalWorkflowResource::collection($paginator));
    }

    public function store(StoreApprovalWorkflowRequest $request): JsonResponse
    {
        $this->authorize('create', ApprovalWorkflow::class);

        $workflow = $this->workflows->create($request->safe()->except('steps'), $request->validated('steps'));

        return ApiResponse::success(new ApprovalWorkflowResource($workflow), 'Workflow berhasil dibuat.', status: 201);
    }

    public function show(ApprovalWorkflow $approvalWorkflow): JsonResponse
    {
        $this->authorize('view', $approvalWorkflow);

        return ApiResponse::success(new ApprovalWorkflowResource($approvalWorkflow->load('steps')));
    }

    public function destroy(ApprovalWorkflow $approvalWorkflow): JsonResponse
    {
        $this->authorize('delete', $approvalWorkflow);

        $this->workflows->delete($approvalWorkflow);

        return ApiResponse::success(message: 'Workflow berhasil dihapus.');
    }
}
