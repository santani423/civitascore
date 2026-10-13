<?php

namespace Modules\Admission\Enums;

enum QuotaMode: string
{
    case Period = 'period';
    case WaveCap = 'wave_cap';

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
