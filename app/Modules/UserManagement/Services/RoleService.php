<?php

namespace Modules\UserManagement\Services;

use App\Support\Http\Exceptions\ConflictException;
use App\Support\Tenancy\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\UserManagement\Models\Role;
use Modules\UserManagement\Support\PermissionRegistry;

class RoleService
{
    public function __construct(private readonly TenantContext $tenant) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Role
    {
        $data['university_id'] ??= $this->tenant->universityId();
        $data['slug'] ??= $this->uniqueSlug($data['name'], $data['university_id']);

        return DB::transaction(fn (): Role => Role::create($data));
    }

    private function uniqueSlug(string $name, ?string $universityId): string
    {
        $base = Str::slug($name, '_');
        $slug = $base;
        $suffix = 1;

        while (Role::query()->where('slug', $slug)->where('university_id', $universityId)->exists()) {
            $slug = $base.'_'.++$suffix;
        }

        return $slug;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Role $role, array $data): Role
    {
        DB::transaction(fn () => $role->update($data));

        return $role;
    }

    public function delete(Role $role): void
    {
        if ($role->is_system) {
            throw new ConflictException('Role sistem tidak dapat dihapus.');
        }

        DB::transaction(fn () => $role->delete());
    }

    /**
     * @param  array<int, string>  $permissionIds
     */
    public function syncPermissions(Role $role, array $permissionIds, ?string $grantedBy = null): Role
    {
        DB::transaction(function () use ($role, $permissionIds, $grantedBy): void {
            $role->permissions()->sync(
                collect($permissionIds)->mapWithKeys(fn (string $id): array => [
                    $id => ['granted_by' => $grantedBy, 'created_at' => now()],
                ])->all(),
            );
        });

        // sync() writes pivot rows via the query builder directly, so it
        // never fires RolePermission's create/delete model events — flush
        // explicitly rather than relying on those hooks for this path.
        PermissionRegistry::flushAll();

        return $role->load('permissions');
    }
}
