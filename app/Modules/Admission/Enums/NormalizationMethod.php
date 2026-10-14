<?php

namespace Modules\Admission\Enums;

enum NormalizationMethod: string
{
    case Minmax = 'minmax';
    case ZscoreSession = 'zscore_session';

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
