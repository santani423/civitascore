<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Modules\Academic\Database\Seeders\StudentUserAccountSeeder;
use Modules\Notification\Database\Seeders\NotificationChannelSeeder;
use Modules\SystemSetting\Database\Seeders\FeatureFlagSeeder;
use Modules\SystemSetting\Database\Seeders\SystemSettingSeeder;
use Modules\Tenancy\Database\Seeders\DemoUniversitiesSeeder;
use Modules\Tenancy\Database\Seeders\TenancySeeder;
use Modules\UserManagement\Database\Seeders\OrganizationalRoleSeeder;
use Modules\UserManagement\Database\Seeders\RolePermissionSeeder;
use Modules\UserManagement\Models\Role;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database. Every step is idempotent
     * (updateOrCreate) so re-running against an existing database is safe.
     */
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            OrganizationalRoleSeeder::class,
            SystemSettingSeeder::class,
            FeatureFlagSeeder::class,
            NotificationChannelSeeder::class,
            TenancySeeder::class,
        ]);

        $superAdmin = User::query()->updateOrCreate(
            ['email' => config('app.super_admin.email')],
            [
                'name' => 'Super Admin',
                'password' => config('app.super_admin.password'),
                'email_verified_at' => now(),
                'is_active' => true,
            ],
        );

        $superAdminRole = Role::query()->where('slug', 'super_admin')->firstOrFail();

        if (! $superAdmin->roles()->where('role_id', $superAdminRole->id)->exists()) {
            $superAdmin->roles()->attach($superAdminRole->id, ['assigned_at' => now()]);
        }

        // Baseline authenticated-but-privilege-less account (role `staff`,
        // no university membership) — proves RBAC denies admin endpoints
        // correctly even for a logged-in user with zero grants.
        $staff = User::query()->updateOrCreate(
            ['email' => 'staff@civitasone.test'],
            ['name' => 'Staff Demo', 'password' => 'password', 'email_verified_at' => now(), 'is_active' => true],
        );

        $staffRole = Role::query()->where('slug', 'staff')->first();

        if ($staffRole && ! $staff->roles()->where('role_id', $staffRole->id)->exists()) {
            $staff->roles()->attach($staffRole->id, ['assigned_at' => now()]);
        }

        $this->call(DemoUniversitiesSeeder::class);

        // Backfills students.user_id so every mahasiswa gets a login
        // account (email = students.email, password = NIM) instead of
        // being left unlinked — must run after DemoUniversitiesSeeder,
        // which is what actually creates the Student rows.
        $this->call(StudentUserAccountSeeder::class);
    }
}
