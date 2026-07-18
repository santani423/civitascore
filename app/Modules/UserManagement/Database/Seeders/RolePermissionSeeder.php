<?php

namespace Modules\UserManagement\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\UserManagement\Enums\PermissionAction;
use Modules\UserManagement\Enums\PermissionScope;
use Modules\UserManagement\Models\Permission;
use Modules\UserManagement\Models\Role;

/**
 * Seeds exactly the permissions actually referenced by Phase 1 route
 * middleware/policies (see the resource => actions map below) plus two
 * roles: `super_admin` (all permissions) and `staff` (empty baseline —
 * authenticated but privilege-less, used to prove RBAC denies correctly).
 * Business roles (mahasiswa, dosen, ...) are deferred to the phases that
 * introduce their real permission sets.
 */
class RolePermissionSeeder extends Seeder
{
    /**
     * @var array<string, array<int, PermissionAction>>
     */
    private const PERMISSION_MAP = [
        'roles' => [PermissionAction::Create, PermissionAction::Read, PermissionAction::Update, PermissionAction::Delete],
        'permissions' => [PermissionAction::Create, PermissionAction::Read, PermissionAction::Update, PermissionAction::Delete],
        'user_roles' => [PermissionAction::Create, PermissionAction::Read, PermissionAction::Delete],
        'audit_logs' => [PermissionAction::Read],
        'system_settings' => [PermissionAction::Read, PermissionAction::Update],
        'feature_flags' => [PermissionAction::Read, PermissionAction::Update],
        'file_uploads' => [PermissionAction::Read, PermissionAction::Delete],
        'notification_templates' => [PermissionAction::Create, PermissionAction::Read, PermissionAction::Update],
        'notification_channels' => [PermissionAction::Read, PermissionAction::Update],
        'approval_workflows' => [PermissionAction::Create, PermissionAction::Read, PermissionAction::Delete],
        'approval_requests' => [PermissionAction::Read],
    ];

    public function run(): void
    {
        $permissions = collect();

        foreach (self::PERMISSION_MAP as $resource => $actions) {
            foreach ($actions as $action) {
                $permissions->push(Permission::query()->updateOrCreate(
                    ['slug' => Permission::slugFor($resource, $action)],
                    [
                        'name' => ucfirst($action->value)." {$resource}",
                        'scope' => PermissionScope::Data,
                        'action' => $action,
                        'resource' => $resource,
                        'is_system' => true,
                    ],
                ));
            }
        }

        $superAdmin = Role::query()->updateOrCreate(
            ['slug' => 'super_admin'],
            ['name' => 'Super Admin', 'description' => 'Akses penuh ke seluruh sistem.', 'is_system' => true],
        );

        $superAdmin->permissions()->sync($permissions->pluck('id')->all());

        Role::query()->updateOrCreate(
            ['slug' => 'staff'],
            ['name' => 'Staff', 'description' => 'Baseline minimal untuk pengguna terautentikasi tanpa privilese khusus.', 'is_system' => true],
        );
    }
}
