<?php

namespace Modules\Admission\Enums;

/**
 * Pemisahan tujuan asesmen (prompt bagian 2): hasil eksplorasi tidak pernah
 * menjadi bahan seleksi. Tujuan `admissions` belum dapat dipublikasikan.
 */
enum AssessmentPurpose: string
{
    case Exploratory = 'exploratory';
    case Admissions = 'admissions';

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
