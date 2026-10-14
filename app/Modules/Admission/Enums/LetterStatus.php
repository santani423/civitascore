<?php

namespace Modules\Admission\Enums;

enum LetterStatus: string
{
    case Pending = 'pending';
    case Issued = 'issued';
    case Revoked = 'revoked';

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
