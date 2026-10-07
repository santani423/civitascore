<?php

namespace Modules\Academic\Support;

use App\Support\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Modules\Tenancy\Models\University;

/**
 * "Sekarang" menurut zona waktu universitas (universities.timezone), bukan
 * zona waktu server (UTC). Jam kuliah di class_schedules dan periode KRS
 * adalah waktu dinding lokal kampus, jadi status "sedang berlangsung"/
 * "hari ini"/"periode KRS terbuka" harus dibandingkan dalam zona yang sama.
 * Waktu absolut (jadwal ujian, deadline tugas) tetap timestampTz dan tidak
 * butuh kelas ini.
 */
class AcademicClock
{
    private const DEFAULT_TIMEZONE = 'Asia/Jakarta';

    /** @var array<string, string> */
    private array $timezones = [];

    public function timezone(?string $universityId = null): string
    {
        $universityId ??= app(TenantContext::class)->universityId();

        if ($universityId === null) {
            return self::DEFAULT_TIMEZONE;
        }

        return $this->timezones[$universityId] ??= University::query()->whereKey($universityId)->value('timezone')
            ?: self::DEFAULT_TIMEZONE;
    }

    public function now(?string $universityId = null): CarbonImmutable
    {
        return CarbonImmutable::now($this->timezone($universityId));
    }
}
