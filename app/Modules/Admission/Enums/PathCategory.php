<?php

namespace Modules\Admission\Enums;

enum PathCategory: string
{
    case Regular = 'regular';
    case Achievement = 'achievement';
    case KipAffirmation = 'kip_affirmation';
    case Transfer = 'transfer';
    case Postgraduate = 'postgraduate';
    case Rpl = 'rpl';
    case Scholarship = 'scholarship';
    case Partnership = 'partnership';
    case International = 'international';
    case Other = 'other';

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
