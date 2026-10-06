<?php

namespace Modules\HumanResource\Enums;

enum LecturerStatus: string
{
    case Permanent = 'permanent';
    case NonPermanent = 'non_permanent';
    case Guest = 'guest';
    case PartTime = 'part_time';

    public function label(): string
    {
        return match ($this) {
            self::Permanent => 'Dosen Tetap',
            self::NonPermanent => 'Dosen Tidak Tetap',
            self::Guest => 'Dosen Tamu',
            self::PartTime => 'Dosen Luar Biasa',
        };
    }
}
