<?php

namespace Modules\HumanResource\Enums;

enum TransferStatus: string
{
    case Scheduled = 'scheduled';
    case Applied = 'applied';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Scheduled => 'Terjadwal',
            self::Applied => 'Diterapkan',
            self::Cancelled => 'Dibatalkan',
        };
    }
}
