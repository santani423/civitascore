<?php

namespace Modules\Admission\Enums;

enum DuplicateFlagStatus: string
{
    case Open = 'open';
    case ConfirmedDuplicate = 'confirmed_duplicate';
    case NotDuplicate = 'not_duplicate';
    case Merged = 'merged';

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
