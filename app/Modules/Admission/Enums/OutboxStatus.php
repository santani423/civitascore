<?php

namespace Modules\Admission\Enums;

enum OutboxStatus: string
{
    case Pending = 'pending';
    case Dispatched = 'dispatched';
    case Failed = 'failed';

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
