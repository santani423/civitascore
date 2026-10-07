<?php

use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Modules\Academic\Models\Employee;
use Modules\Academic\Models\Lecturer;
use Modules\Tenancy\Enums\MembershipStatus;
use Modules\Tenancy\Enums\MembershipType;
use Modules\Tenancy\Models\University;
use Modules\Tenancy\Models\UserUniversity;
use Modules\UserManagement\Database\Seeders\OrganizationalRoleSeeder;
use Modules\UserManagement\Database\Seeders\RolePermissionSeeder;
use Modules\UserManagement\Enums\PermissionAction;
use Modules\UserManagement\Enums\PermissionScope;
use Modules\UserManagement\Models\Permission;
use Modules\UserManagement\Models\Role;
use Modules\UserManagement\Models\UserRole;
use Modules\UserManagement\Support\PermissionRegistry;

/*
| Helper bersama test Modul SDM. Dimuat lewat require_once dari setiap file
| test (bukan file *Test.php, jadi tidak dieksekusi Pest sebagai test).
| Test SDM memakai role & permission SUNGGUHAN dari seeder
| (OrganizationalRoleSeeder) — yang diuji adalah konfigurasi role yang
| benar-benar dipakai produksi, bukan role buatan test.
*/

if (! function_exists('hrSeedRoles')) {
    function hrSeedRoles(): void
    {
        app(RolePermissionSeeder::class)->run();
        app(OrganizationalRoleSeeder::class)->run();
        PermissionRegistry::flushAll();
    }

    function hrMembership(User $user, University $university, MembershipType $type = MembershipType::Staff): void
    {
        UserUniversity::query()->create([
            'user_id' => $user->id,
            'university_id' => $university->id,
            'membership_type' => $type,
            'status' => MembershipStatus::Active,
            'joined_at' => now(),
            'is_default' => true,
        ]);
    }

    /**
     * User baru dengan role organisasi (slug global dari seeder) di satu
     * universitas.
     */
    function hrUserWithRole(University $university, string $roleSlug, MembershipType $type = MembershipType::Staff): User
    {
        $user = User::factory()->create();
        hrMembership($user, $university, $type);

        UserRole::query()->create([
            'user_id' => $user->id,
            'role_id' => Role::query()->where('slug', $roleSlug)->whereNull('university_id')->firstOrFail()->id,
            'university_id' => $university->id,
            'assigned_at' => now(),
        ]);
        PermissionRegistry::flushAll();

        return $user;
    }

    /**
     * User dengan kumpulan permission spesifik (role kustom tenant).
     *
     * @param  array<int, string>  $permissionSlugs
     */
    function hrUserWithPermissions(University $university, array $permissionSlugs): User
    {
        $user = User::factory()->create();
        hrMembership($user, $university);

        $role = Role::factory()->create(['university_id' => $university->id]);
        $role->permissions()->sync(collect($permissionSlugs)->map(function (string $slug) {
            [$resource, $action] = explode('.', $slug, 2);

            return Permission::query()->firstOrCreate(
                ['slug' => $slug],
                ['name' => $slug, 'resource' => $resource, 'action' => PermissionAction::from($action), 'scope' => PermissionScope::Data],
            )->id;
        }));

        UserRole::query()->create(['user_id' => $user->id, 'role_id' => $role->id, 'university_id' => $university->id, 'assigned_at' => now()]);
        PermissionRegistry::flushAll();

        return $user;
    }

    /**
     * Jalankan callback di konteks tenant (factory model TenantScoped butuh
     * TenantContext), lalu kembalikan konteks ke kosong supaya tidak bocor
     * ke request berikutnya.
     *
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    function hrInTenant(University $university, callable $callback): mixed
    {
        $context = app(TenantContext::class);
        $context->setUniversityId($university->id);

        try {
            return $callback();
        } finally {
            $context->setUniversityId(null);
        }
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    function hrStaff(University $university, array $attributes = []): Employee
    {
        return hrInTenant($university, fn () => Employee::factory()->create([
            'university_id' => $university->id,
            'employee_type' => 'staff',
            ...$attributes,
        ]));
    }

    /**
     * Dosen lengkap: baris employees + lecturers yang saling tertaut.
     *
     * @param  array<string, mixed>  $attributes
     */
    function hrLecturer(University $university, array $attributes = []): Employee
    {
        return hrInTenant($university, function () use ($university, $attributes): Employee {
            $employee = Employee::factory()->create([
                'university_id' => $university->id,
                'employee_type' => 'lecturer',
                'position' => 'Dosen',
                ...$attributes,
            ]);

            Lecturer::factory()->create([
                'university_id' => $university->id,
                'employee_id' => $employee->id,
                'faculty_id' => null,
                'name' => $employee->name,
                'email' => $employee->email,
            ]);

            return $employee->refresh();
        });
    }

    /**
     * @return array<string, string>
     */
    function hrHeaders(University $university): array
    {
        return ['X-University-ID' => $university->id];
    }
}
