<?php

namespace Modules\SystemSetting\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\SystemSetting\Enums\SettingValueType;
use Modules\SystemSetting\Models\SystemSetting;

/**
 * Seeds only settings actually read by Phase 1 code — no decorative
 * entries. `file.max_upload_size_mb` gates StoreFileUploadRequest's max
 * file size rule; `security.max_login_attempts` gates the login
 * rate-limiter in AuthenticateUserAction.
 */
class SystemSettingSeeder extends Seeder
{
    public function run(): void
    {
        SystemSetting::query()->updateOrCreate(
            ['key' => 'file.max_upload_size_mb'],
            [
                'value' => '10',
                'type' => SettingValueType::Integer,
                'group' => 'file',
                'description' => 'Ukuran maksimum unggahan file dalam megabyte.',
                'is_public' => false,
            ],
        );

        SystemSetting::query()->updateOrCreate(
            ['key' => 'security.max_login_attempts'],
            [
                'value' => '5',
                'type' => SettingValueType::Integer,
                'group' => 'security',
                'description' => 'Jumlah maksimum percobaan login gagal sebelum diblokir sementara.',
                'is_public' => false,
            ],
        );
    }
}
