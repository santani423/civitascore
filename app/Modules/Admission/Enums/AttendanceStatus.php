<?php

namespace Modules\Admission\Enums;

enum AttendanceStatus: string
{
    case Registered = 'registered';
    case Present = 'present';
    case Absent = 'absent';
    case Excused = 'excused';
    case Disqualified = 'disqualified';

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
