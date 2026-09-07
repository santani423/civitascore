<?php

namespace Modules\SystemSetting\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\SystemSetting\Models\SystemSetting;

class SystemSettingService
{
    private const CACHE_PREFIX = 'system_setting:';

    private const CACHE_TTL_SECONDS = 3600;

    public function get(string $key, mixed $default = null): mixed
    {
        return Cache::remember(
            self::CACHE_PREFIX.$key,
            self::CACHE_TTL_SECONDS,
            function () use ($key, $default): mixed {
                $setting = SystemSetting::query()->where('key', $key)->first();

                return $setting ? $setting->castedValue() : $default;
            },
        );
    }

    public function update(SystemSetting $setting, string $value, User $updatedBy): SystemSetting
    {
        DB::transaction(function () use ($setting, $value, $updatedBy): void {
            $setting->update(['value' => $value, 'updated_by' => $updatedBy->id]);
        });

        Cache::forget(self::CACHE_PREFIX.$setting->key);

        return $setting;
    }
}
