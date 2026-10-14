<?php

namespace Modules\Admission\Enums;

enum SchemeStatus: string
{
    case Draft = 'draft';
    case Locked = 'locked';
    case Retired = 'retired';

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
