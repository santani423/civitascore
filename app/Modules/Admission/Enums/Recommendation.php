<?php

namespace Modules\Admission\Enums;

enum Recommendation: string
{
    case Pass = 'pass';
    case Waitlist = 'waitlist';
    case Fail = 'fail';

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
