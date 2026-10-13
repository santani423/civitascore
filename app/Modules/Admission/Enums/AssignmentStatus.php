<?php

namespace Modules\Admission\Enums;

enum AssignmentStatus: string
{
    case Active = 'active';
    case Released = 'released';
    case Completed = 'completed';
    case Expired = 'expired';

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
