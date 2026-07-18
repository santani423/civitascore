<?php

namespace Modules\UserManagement\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Modules\UserManagement\Actions\AssignRoleAction;
use Modules\UserManagement\Actions\RevokeRoleAction;
use Modules\UserManagement\Models\Role;
use Modules\UserManagement\Models\UserRole;
use Modules\UserManagement\Requests\AssignRoleRequest;
use Modules\UserManagement\Resources\RoleResource;

class UserRoleController extends Controller
{
    public function __construct(
        private readonly AssignRoleAction $assignRole,
        private readonly RevokeRoleAction $revokeRole,
    ) {}

    public function index(User $user): JsonResponse
    {
        $this->authorize('viewAny', UserRole::class);

        return ApiResponse::success(RoleResource::collection($user->roles));
    }

    public function store(AssignRoleRequest $request, User $user): JsonResponse
    {
        $this->authorize('create', UserRole::class);

        $role = Role::query()->where('id', $request->validated('role_id'))->firstOrFail();
        $expiresAt = $request->validated('expires_at');

        $this->assignRole->execute(
            $user,
            $role,
            $request->user(),
            $expiresAt ? Carbon::parse($expiresAt) : null,
        );

        return ApiResponse::success(RoleResource::collection($user->roles()->get()), 'Role berhasil ditambahkan.', status: 201);
    }

    public function destroy(User $user, Role $role): JsonResponse
    {
        $this->authorize('delete', UserRole::class);

        $this->revokeRole->execute($user, $role);

        return ApiResponse::success(message: 'Role berhasil dicabut.');
    }
}
