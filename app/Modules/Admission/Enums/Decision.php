<?php

namespace Modules\Admission\Enums;

/** SM-09 dimensi keputusan — setiap perubahan = baris versi baru. */
enum Decision: string
{
    case Accepted = 'accepted';
    case Waitlisted = 'waitlisted';
    case Rejected = 'rejected';
    case Withdrawn = 'withdrawn';

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
