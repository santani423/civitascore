<?php

namespace Modules\Admission\Enums;

enum ReviewDecision: string
{
    case Approved = 'approved';
    case Rejected = 'rejected';
    case RevisionRequested = 'revision_requested';
    case Reopened = 'reopened';

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
