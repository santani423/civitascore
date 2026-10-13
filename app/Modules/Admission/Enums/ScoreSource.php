<?php

namespace Modules\Admission\Enums;

enum ScoreSource: string
{
    case Manual = 'manual';
    case Import = 'import';
    case Computed = 'computed';
    case CbtSync = 'cbt_sync';

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
