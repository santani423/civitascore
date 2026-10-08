<?php

namespace Modules\SystemSetting\Services;

use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Support\Facades\Cache;
use Modules\SystemSetting\Models\FeatureFlag;
use Modules\Tenancy\Models\UniversityFeatureFlag;

/**
 * Nilai efektif sebuah flag untuk satu universitas:
 *
 * 1. override tenant (university_feature_flags) bila ada;
 * 2. selain itu nilai global (feature_flags) — default untuk semua tenant;
 * 3. key yang tidak dikenal = mati (aman: fitur baru tidak pernah menyala
 *    tanpa sengaja).
 *
 * Tanpa $universityId, tenant aktif (TenantContext) yang dipakai; konteks
 * platform (tanpa tenant) hanya membaca nilai global. Override dan nilai
 * global di-cache terpisah, jadi mengubah default global langsung berlaku
 * bagi semua tenant yang tidak meng-override-nya.
 */
class FeatureFlagService
{
    private const CACHE_PREFIX = 'feature_flag:';

    private const OVERRIDE_CACHE_PREFIX = 'feature_flag_override:';

    private const CACHE_TTL_SECONDS = 3600;

    public function __construct(private readonly TenantContext $tenant) {}

    public function isEnabled(string $key, ?string $universityId = null): bool
    {
        $universityId ??= $this->tenant->universityId();

        if ($universityId !== null) {
            $override = $this->overrideFor($key, $universityId);

            if ($override !== null) {
                return $override;
            }
        }

        return $this->defaultFor($key);
    }

    /** Nilai global (default untuk tenant tanpa override). */
    public function defaultFor(string $key): bool
    {
        return (bool) Cache::remember(
            self::CACHE_PREFIX.$key,
            self::CACHE_TTL_SECONDS,
            fn (): bool => (bool) FeatureFlag::query()->where('key', $key)->value('is_enabled'),
        );
    }

    /** Override universitas, atau null bila universitas mengikuti nilai global. */
    public function overrideFor(string $key, string $universityId): ?bool
    {
        // Cache tidak bisa menyimpan null secara andal — "tanpa override"
        // disimpan sebagai string tersendiri.
        $value = Cache::remember(
            self::overrideCacheKey($key, $universityId),
            self::CACHE_TTL_SECONDS,
            function () use ($key, $universityId): string {
                $enabled = UniversityFeatureFlag::query()
                    ->where('university_id', $universityId)
                    ->where('key', $key)
                    ->value('is_enabled');

                return $enabled === null ? 'inherit' : ((bool) $enabled ? 'on' : 'off');
            },
        );

        return match ($value) {
            'on' => true,
            'off' => false,
            default => null,
        };
    }

    /**
     * Mengubah flag dari layar Feature Flag: di konteks tenant hanya
     * universitas itu yang berubah (override); hanya konteks platform
     * (Super Admin, tanpa tenant) yang mengubah nilai global. Pemegang
     * feature_flags.update di sebuah tenant tidak pernah bisa mengubah
     * perilaku universitas lain.
     */
    public function set(FeatureFlag $flag, bool $enabled, User $updatedBy): FeatureFlag
    {
        $universityId = $this->tenant->universityId();

        if ($universityId === null) {
            return $this->toggle($flag, $enabled, $updatedBy);
        }

        $this->setOverride($flag, $universityId, $enabled, $updatedBy);

        return $flag;
    }

    /** Mengubah nilai global. */
    public function toggle(FeatureFlag $flag, bool $enabled, User $updatedBy): FeatureFlag
    {
        $flag->update(['is_enabled' => $enabled, 'updated_by' => $updatedBy->id]);

        Cache::forget(self::CACHE_PREFIX.$flag->key);

        return $flag;
    }

    /** Cache override dibersihkan oleh event model UniversityFeatureFlag. */
    public function setOverride(FeatureFlag $flag, string $universityId, bool $enabled, ?User $updatedBy = null): UniversityFeatureFlag
    {
        return UniversityFeatureFlag::query()->updateOrCreate(
            ['university_id' => $universityId, 'key' => $flag->key],
            ['is_enabled' => $enabled, 'updated_by' => $updatedBy?->id],
        );
    }

    /** Universitas kembali mengikuti nilai global. */
    public function clearOverride(FeatureFlag $flag, string $universityId): void
    {
        // Dihapus per model (bukan bulk delete) supaya event audit & cache berjalan.
        UniversityFeatureFlag::query()
            ->where('university_id', $universityId)
            ->where('key', $flag->key)
            ->get()
            ->each(fn (UniversityFeatureFlag $override) => $override->delete());
    }

    public static function forgetOverride(string $key, string $universityId): void
    {
        Cache::forget(self::overrideCacheKey($key, $universityId));
    }

    private static function overrideCacheKey(string $key, string $universityId): string
    {
        return self::OVERRIDE_CACHE_PREFIX.$universityId.':'.$key;
    }
}
