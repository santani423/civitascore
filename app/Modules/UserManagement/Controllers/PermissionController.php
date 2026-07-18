<?php

namespace Modules\UserManagement\Controllers;

use App\Http\Controllers\Controller;
use App\Support\Http\ApiResponse;
use App\Support\Http\ListQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\UserManagement\Models\Permission;
use Modules\UserManagement\Requests\StorePermissionRequest;
use Modules\UserManagement\Requests\UpdatePermissionRequest;
use Modules\UserManagement\Resources\PermissionResource;
use Modules\UserManagement\Services\PermissionService;

class PermissionController extends Controller
{
    public function __construct(private readonly PermissionService $permissions) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Permission::class);

        $paginator = ListQuery::paginate(
            query: Permission::query(),
            request: $request,
            searchable: ['name', 'slug', 'resource'],
            filterable: ['scope', 'action', 'resource', 'is_system'],
            sortable: ['name', 'created_at'],
        );

        return ApiResponse::paginated(PermissionResource::collection($paginator));
    }

    public function store(StorePermissionRequest $request): JsonResponse
    {
        $this->authorize('create', Permission::class);

        $permission = $this->permissions->create($request->validated());

        return ApiResponse::success(new PermissionResource($permission), 'Permission berhasil dibuat.', status: 201);
    }

    public function show(Permission $permission): JsonResponse
    {
        $this->authorize('view', $permission);

        return ApiResponse::success(new PermissionResource($permission));
    }

    public function update(UpdatePermissionRequest $request, Permission $permission): JsonResponse
    {
        $this->authorize('update', $permission);

        $permission = $this->permissions->update($permission, $request->validated());

        return ApiResponse::success(new PermissionResource($permission), 'Permission berhasil diperbarui.');
    }

    public function destroy(Permission $permission): JsonResponse
    {
        $this->authorize('delete', $permission);

        $this->permissions->delete($permission);

        return ApiResponse::success(message: 'Permission berhasil dihapus.');
    }
}
