<?php

namespace Modules\AuditLog\Controllers;

use App\Http\Controllers\Controller;
use App\Support\Http\ApiResponse;
use App\Support\Http\ListQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\AuditLog\Models\AuditLog;
use Modules\AuditLog\Resources\AuditLogResource;

class AuditLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', AuditLog::class);

        $paginator = ListQuery::paginate(
            query: AuditLog::query()->with('user')->latest('created_at'),
            request: $request,
            filterable: ['action', 'user_id', 'auditable_type'],
            sortable: ['created_at'],
        );

        return ApiResponse::paginated(AuditLogResource::collection($paginator));
    }

    public function show(AuditLog $auditLog): JsonResponse
    {
        $this->authorize('view', $auditLog);

        return ApiResponse::success(new AuditLogResource($auditLog->load('user')));
    }
}
