<?php

namespace Modules\Admission\Enums;

enum ReconciliationResolution: string
{
    case AutoFixed = 'auto_fixed';
    case RefundCreated = 'refund_created';
    case WrittenOff = 'written_off';
    case Investigated = 'investigated';

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
