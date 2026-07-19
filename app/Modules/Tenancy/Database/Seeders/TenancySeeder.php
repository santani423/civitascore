<?php

namespace Modules\Tenancy\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Tenancy\Models\PlatformModule;
use Modules\Tenancy\Models\SubscriptionPlan;

/**
 * Platform-wide catalogs: subscription plans and the business-module list
 * a university can enable. Not tenant data — seeded once, shared by every
 * university via university_subscriptions / university_modules.
 */
class TenancySeeder extends Seeder
{
    private const PLANS = [
        ['code' => 'trial', 'name' => 'Trial', 'price' => 0, 'limits' => ['max_users' => 20, 'max_storage_gb' => 2]],
        ['code' => 'starter', 'name' => 'Starter', 'price' => 2_500_000, 'limits' => ['max_users' => 100, 'max_storage_gb' => 10]],
        ['code' => 'professional', 'name' => 'Professional', 'price' => 8_000_000, 'limits' => ['max_users' => 500, 'max_storage_gb' => 50]],
        ['code' => 'enterprise', 'name' => 'Enterprise', 'price' => 25_000_000, 'limits' => ['max_users' => null, 'max_storage_gb' => 500]],
    ];

    private const MODULES = [
        'academic' => 'Akademik',
        'admission' => 'Penerimaan Mahasiswa Baru',
        'finance' => 'Keuangan',
        'hr' => 'Sumber Daya Manusia',
        'payroll' => 'Penggajian',
        'library' => 'Perpustakaan',
        'asset' => 'Sarana dan Prasarana',
        'research' => 'Penelitian',
        'community_service' => 'Pengabdian kepada Masyarakat',
        'alumni' => 'Alumni',
        'graduation' => 'Kelulusan dan Wisuda',
        'mbkm' => 'MBKM',
        'complaint' => 'Pengaduan',
        'document' => 'Surat dan Dokumen',
        'mobile_app' => 'Aplikasi Mobile',
    ];

    public function run(): void
    {
        foreach (self::PLANS as $plan) {
            SubscriptionPlan::query()->updateOrCreate(
                ['code' => $plan['code']],
                ['name' => $plan['name'], 'price' => $plan['price'], 'limits' => $plan['limits'], 'billing_period' => 'monthly', 'is_active' => true],
            );
        }

        foreach (self::MODULES as $key => $name) {
            PlatformModule::query()->updateOrCreate(['key' => $key], ['name' => $name, 'is_active' => true]);
        }
    }
}
