<?php

namespace Modules\HumanResource\Enums;

enum LeaveType: string implements HasLabel
{
    case Annual = 'annual';
    case Sick = 'sick';
    case Maternity = 'maternity';
    case Special = 'special';
    case Permission = 'permission';

    public function label(): string
    {
        return match ($this) {
            self::Annual => 'Cuti Tahunan',
            self::Sick => 'Cuti Sakit',
            self::Maternity => 'Cuti Melahirkan',
            self::Special => 'Cuti Khusus',
            self::Permission => 'Izin',
        };
    }
}
