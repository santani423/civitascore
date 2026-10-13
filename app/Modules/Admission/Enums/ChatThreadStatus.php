<?php

namespace Modules\Admission\Enums;

enum ChatThreadStatus: string
{
    case Open = 'open';
    case PendingApplicant = 'pending_applicant';
    case Closed = 'closed';

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
