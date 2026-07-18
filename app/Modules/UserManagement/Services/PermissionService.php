<?php

namespace Modules\UserManagement\Services;

use App\Support\Http\Exceptions\ConflictException;
use Illuminate\Support\Facades\DB;
use Modules\UserManagement\Enums\PermissionAction;
use Modules\UserManagement\Models\Permission;

class PermissionService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Permission
    {
        $action = $data['action'] instanceof PermissionAction ? $data['action'] : PermissionAction::from($data['action']);
        $data['slug'] ??= Permission::slugFor($data['resource'], $action);

        if (Permission::query()->where('slug', $data['slug'])->exists()) {
            throw new ConflictException('Permission dengan slug ini sudah ada.');
        }

        return DB::transaction(fn (): Permission => Permission::create($data));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Permission $permission, array $data): Permission
    {
        DB::transaction(fn () => $permission->update($data));

        return $permission;
    }

    public function delete(Permission $permission): void
    {
        if ($permission->is_system) {
            throw new ConflictException('Permission sistem tidak dapat dihapus.');
        }

        DB::transaction(fn () => $permission->delete());
    }
}
