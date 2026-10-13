<?php

namespace Modules\Admission\Enums;

enum ScoreType: string
{
    case Numeric = 'numeric';
    case PassFail = 'pass_fail';

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
