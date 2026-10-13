<?php

namespace Modules\Admission\Enums;

enum FailReason: string
{
    case BelowThreshold = 'below_threshold';
    case ComponentMinimum = 'component_minimum';
    case Absent = 'absent';
    case QuotaFullNoWaitlist = 'quota_full_no_waitlist';
    case Disqualified = 'disqualified';
    case IncompleteScores = 'incomplete_scores';

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
