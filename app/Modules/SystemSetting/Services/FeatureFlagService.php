<?php

namespace Modules\SystemSetting\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Modules\SystemSetting\Models\FeatureFlag;

class FeatureFlagService
{
    private const CACHE_PREFIX = 'feature_flag:';

    private const CACHE_TTL_SECONDS = 3600;

    public function isEnabled(string $key): bool
    {
        return (bool) Cache::remember(
            self::CACHE_PREFIX.$key,
            self::CACHE_TTL_SECONDS,
            fn (): bool => (bool) FeatureFlag::query()->where('key', $key)->value('is_enabled'),
        );
    }

    public function toggle(FeatureFlag $flag, bool $enabled, User $updatedBy): FeatureFlag
    {
        $flag->update(['is_enabled' => $enabled, 'updated_by' => $updatedBy->id]);

        Cache::forget(self::CACHE_PREFIX.$flag->key);

        return $flag;
    }
}
