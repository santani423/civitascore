<?php

namespace Modules\Admission\Enums;

enum CheckEvaluation: string
{
    case Auto = 'auto';
    case Manual = 'manual';

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
