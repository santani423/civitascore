<?php

namespace Modules\SystemSetting\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\SystemSetting\Models\FeatureFlag;

/**
 * Both flags gate real, tested branches — `file_upload.virus_scan_enabled`
 * in FileUploadService, `approval_workflow.delegation_enabled` in
 * ApprovalRequestStepPolicy::delegate().
 */
class FeatureFlagSeeder extends Seeder
{
    public function run(): void
    {
        FeatureFlag::query()->updateOrCreate(
            ['key' => 'file_upload.virus_scan_enabled'],
            ['name' => 'Pemindaian Virus Unggahan File', 'description' => 'Jalankan pemindaian virus pada setiap file yang diunggah.', 'is_enabled' => true],
        );

        FeatureFlag::query()->updateOrCreate(
            ['key' => 'approval_workflow.delegation_enabled'],
            ['name' => 'Delegasi Langkah Persetujuan', 'description' => 'Izinkan approver mendelegasikan langkah persetujuan ke pengguna lain.', 'is_enabled' => true],
        );
    }
}
