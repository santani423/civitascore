<?php

namespace Modules\SystemSetting\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Academic\Support\AcademicFeature;
use Modules\SystemSetting\Models\FeatureFlag;

/**
 * Every flag gates a real, tested branch — `file_upload.virus_scan_enabled`
 * in FileUploadService, `approval_workflow.delegation_enabled` in
 * ApprovalRequestStepPolicy::delegate(), the `academic.*` flags in
 * Modules\Academic\Support\AcademicFeature. These are the global defaults;
 * a university overrides them in university_feature_flags.
 *
 * The academic flags are created disabled and their global value is never
 * overwritten on re-seed, so re-running this seeder can't switch a rollout
 * on (or off) for every tenant at once.
 */
class FeatureFlagSeeder extends Seeder
{
    public function run(): void
    {
        FeatureFlag::query()->firstOrCreate(
            ['key' => AcademicFeature::LECTURER_OWNERSHIP],
            [
                'name' => 'Kepemilikan Kelas Dosen',
                'description' => 'Dosen hanya dapat melihat dan mengelola nilai, absensi, dan ujian kelas yang diampunya. Isi dosen pengampu semua kelas sebelum menyalakan.',
                'is_enabled' => false,
            ],
        );

        FeatureFlag::query()->firstOrCreate(
            ['key' => AcademicFeature::SHORT_TERM],
            [
                'name' => 'Semester Pendek',
                'description' => 'Periode Semester Pendek dapat dibuat dan dilihat di universitas ini.',
                'is_enabled' => false,
            ],
        );

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
