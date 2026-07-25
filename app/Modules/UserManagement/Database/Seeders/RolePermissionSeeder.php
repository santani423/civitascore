<?php

namespace Modules\UserManagement\Database\Seeders;

use Illuminate\Cache\Repository as CacheRepository;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Modules\UserManagement\Enums\PermissionAction;
use Modules\UserManagement\Enums\PermissionScope;
use Modules\UserManagement\Models\Permission;
use Modules\UserManagement\Models\Role;

/**
 * Seeds permissions yang digunakan oleh middleware dan policy Phase 1.
 *
 * Role:
 * - super_admin: memiliki seluruh permission.
 * - staff: role dasar tanpa permission.
 */
class RolePermissionSeeder extends Seeder
{
    /**
     * @var array<string, array<int, PermissionAction>>
     */
    private const PERMISSION_MAP = [
        'roles' => [
            PermissionAction::Create,
            PermissionAction::Read,
            PermissionAction::Update,
            PermissionAction::Delete,
        ],
        'permissions' => [
            PermissionAction::Create,
            PermissionAction::Read,
            PermissionAction::Update,
            PermissionAction::Delete,
        ],
        'user_roles' => [
            PermissionAction::Create,
            PermissionAction::Read,
            PermissionAction::Delete,
        ],
        'audit_logs' => [
            PermissionAction::Read,
        ],
        'system_settings' => [
            PermissionAction::Read,
            PermissionAction::Update,
        ],
        'feature_flags' => [
            PermissionAction::Read,
            PermissionAction::Update,
        ],
        'file_uploads' => [
            PermissionAction::Read,
            PermissionAction::Delete,
        ],
        'notification_templates' => [
            PermissionAction::Create,
            PermissionAction::Read,
            PermissionAction::Update,
        ],
        'notification_channels' => [
            PermissionAction::Read,
            PermissionAction::Update,
        ],
        'approval_workflows' => [
            PermissionAction::Create,
            PermissionAction::Read,
            PermissionAction::Delete,
        ],
        'approval_requests' => [
            PermissionAction::Read,
        ],
        'users' => [
            PermissionAction::Read,
        ],
        'platform_universities' => [
            PermissionAction::Create,
            PermissionAction::Read,
            PermissionAction::Update,
        ],
        'platform_statistics' => [
            PermissionAction::Read,
        ],
        'tenant_profile' => [
            PermissionAction::Read,
            PermissionAction::Update,
        ],
        'platform_modules' => [
            PermissionAction::Update,
        ],
        'platform_billing' => [
            PermissionAction::Read,
            PermissionAction::Update,
        ],
        'platform_security' => [
            PermissionAction::Read,
            PermissionAction::Update,
        ],
        'support_sessions' => [
            PermissionAction::Create,
            PermissionAction::Read,
        ],
        'platform_master_data' => [
            PermissionAction::Update,
        ],
        'platform_maintenance' => [
            PermissionAction::Update,
        ],
        'students' => [
            PermissionAction::Read,
        ],
        'lecturers' => [
            PermissionAction::Read,
        ],
        'employees' => [
            PermissionAction::Read,
        ],
        'study_programs' => [
            PermissionAction::Read,
        ],
        'classes' => [
            PermissionAction::Read,
        ],
        'invoices' => [
            PermissionAction::Read,
        ],
        'curriculums' => [
            PermissionAction::Read,
        ],
        'courses' => [
            PermissionAction::Read,
        ],
        'krs' => [
            PermissionAction::Read,
        ],
        'grades' => [
            PermissionAction::Read,
        ],
        'attendance' => [
            PermissionAction::Read,
        ],
        'scholarships' => [
            PermissionAction::Read,
        ],
        'theses' => [
            PermissionAction::Read,
        ],
        'internships' => [
            PermissionAction::Read,
        ],
        'books' => [
            PermissionAction::Read,
        ],
        'alumni' => [
            PermissionAction::Read,
        ],
        'announcements' => [
            PermissionAction::Read,
        ],
        'reports' => [
            PermissionAction::Read,
        ],
    ];

    public function run(): void
    {
        $permissions = $this->seedPermissions();

        $superAdmin = Role::query()->updateOrCreate(
            [
                'slug' => 'super_admin',
                'university_id' => null,
            ],
            [
                'name' => 'Super Admin',
                'description' => 'Akses penuh ke seluruh sistem.',
                'is_system' => true,
            ],
        );

        $superAdmin->permissions()->sync(
            $permissions->pluck('id')->all(),
        );

        Role::query()->updateOrCreate(
            [
                'slug' => 'staff',
                'university_id' => null,
            ],
            [
                'name' => 'Staff',
                'description' => 'Baseline minimal untuk pengguna terautentikasi tanpa privilese khusus.',
                'is_system' => true,
            ],
        );

        $this->flushPermissionCache();
    }

    /**
     * Membuat atau memperbarui seluruh permission sistem.
     *
     * @return Collection<int, Permission>
     */
    private function seedPermissions(): Collection
    {
        $permissions = collect();

        foreach (self::PERMISSION_MAP as $resource => $actions) {
            foreach ($actions as $action) {
                $permission = Permission::query()->updateOrCreate(
                    [
                        'slug' => Permission::slugFor($resource, $action),
                    ],
                    [
                        'name' => sprintf(
                            '%s %s',
                            ucfirst($action->value),
                            str_replace('_', ' ', $resource),
                        ),
                        'scope' => PermissionScope::Data,
                        'action' => $action,
                        'resource' => $resource,
                        'is_system' => true,
                    ],
                );

                $permissions->push($permission);
            }
        }

        return $permissions;
    }

    /**
     * Membersihkan cache permission hanya ketika cache store
     * yang digunakan mendukung cache tags.
     *
     * Driver seperti file dan database tidak mendukung tags,
     * sehingga pemanggilan Cache::tags() harus dilewati.
     */
    private function flushPermissionCache(): void
    {
        /** @var CacheRepository $cache */
        $cache = Cache::store();

        if (! $cache->supportsTags()) {
            return;
        }

        $cache->tags(['permissions'])->flush();
    }
}