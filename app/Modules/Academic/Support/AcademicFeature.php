<?php

namespace Modules\Academic\Support;

use Modules\SystemSetting\Services\FeatureFlagService;

/**
 * Key feature flag Modul Akademik (global di feature_flags, bisa di-override
 * per universitas). Semua default mati — lihat FeatureFlagSeeder.
 */
final class AcademicFeature
{
    /** Dosen hanya mengelola nilai/absensi/ujian kelas yang diampunya (Tahap 0.8). */
    public const LECTURER_OWNERSHIP = 'academic.lecturer_ownership';

    /** Term Semester Pendek boleh dibuat & terlihat (Tahap 1.4). */
    public const SHORT_TERM = 'academic.short_term';

    public function __construct(private readonly FeatureFlagService $flags) {}

    public function lecturerOwnershipEnforced(): bool
    {
        return $this->flags->isEnabled(self::LECTURER_OWNERSHIP);
    }

    public function shortTermEnabled(): bool
    {
        return $this->flags->isEnabled(self::SHORT_TERM);
    }
}
