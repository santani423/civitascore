<?php

namespace Modules\UserManagement\Controllers;

use App\Http\Controllers\Controller;
use App\Support\Http\ApiResponse;
use App\Support\Http\ListQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\UserManagement\Models\Role;
use Modules\UserManagement\Requests\StoreRoleRequest;
use Modules\UserManagement\Requests\SyncRolePermissionsRequest;
use Modules\UserManagement\Requests\UpdateRoleRequest;
use Modules\UserManagement\Resources\RoleResource;
use Modules\UserManagement\Services\RoleService;

class RoleController extends Controller
{
    public function __construct(private readonly RoleService $roles) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Role::class);

        $paginator = ListQuery::paginate(
            query: Role::query(),
            request: $request,
            searchable: ['name', 'slug'],
            filterable: ['is_system'],
            sortable: ['name', 'created_at'],
        );

        return ApiResponse::paginated(RoleResource::collection($paginator));
    }

    public function store(StoreRoleRequest $request): JsonResponse
    {
        $this->authorize('create', Role::class);

        $role = $this->roles->create($request->validated());

        return ApiResponse::success(new RoleResource($role), 'Role berhasil dibuat.', status: 201);
    }

    public function show(Role $role): JsonResponse
    {
        $this->authorize('view', $role);

        return ApiResponse::success(new RoleResource($role->load('permissions')));
    }

    public function update(UpdateRoleRequest $request, Role $role): JsonResponse
    {
        $this->authorize('update', $role);

        $role = $this->roles->update($role, $request->validated());

        return ApiResponse::success(new RoleResource($role), 'Role berhasil diperbarui.');
    }

    public function destroy(Role $role): JsonResponse
    {
        $this->authorize('delete', $role);

        $this->roles->delete($role);

        // 204 must carry no body, which conflicts with the {success,message}
        // envelope every other endpoint returns — 200 keeps the envelope
        // consistent for delete responses too.
        return ApiResponse::success(message: 'Role berhasil dihapus.');
    }

    public function syncPermissions(SyncRolePermissionsRequest $request, Role $role): JsonResponse
    {
        $this->authorize('managePermissions', $role);

        $role = $this->roles->syncPermissions($role, $request->validated('permission_ids'), $request->user()->id);

        return ApiResponse::success(new RoleResource($role), 'Permission role berhasil diperbarui.');
    }
}
