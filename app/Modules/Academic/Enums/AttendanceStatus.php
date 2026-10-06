<?php

namespace Modules\Academic\Enums;

enum AttendanceStatus: string
{
    case Present = 'present';
    case Permitted = 'permitted';
    case Sick = 'sick';
    case Absent = 'absent';

    public function label(): string
    {
        return match ($this) {
            self::Present => 'Hadir',
            self::Permitted => 'Izin',
            self::Sick => 'Sakit',
            self::Absent => 'Alpa',
        };
    }
}
