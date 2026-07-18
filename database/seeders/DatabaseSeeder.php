<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Modules\Notification\Database\Seeders\NotificationChannelSeeder;
use Modules\SystemSetting\Database\Seeders\FeatureFlagSeeder;
use Modules\SystemSetting\Database\Seeders\SystemSettingSeeder;
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
            SystemSettingSeeder::class,
            FeatureFlagSeeder::class,
            NotificationChannelSeeder::class,
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
    }
}
