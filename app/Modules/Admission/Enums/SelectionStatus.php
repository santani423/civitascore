<?php

namespace Modules\Admission\Enums;

use Modules\Admission\Support\TransitionsState;

/**
 * SM-07 — seleksi peserta. `ranked → assessed` hanya untuk koreksi nilai
 * terkunci yang disetujui (BR-31, 06 §H.8).
 */
enum SelectionStatus: string
{
    use TransitionsState;

    case NotEligible = 'not_eligible';
    case Eligible = 'eligible';
    case Scheduled = 'scheduled';
    case Assessed = 'assessed';
    case Absent = 'absent';
    case Disqualified = 'disqualified';
    case Ranked = 'ranked';

    /** @return array<int, self> */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::NotEligible => [self::Eligible],
            self::Eligible => [self::Scheduled, self::Assessed, self::NotEligible, self::Disqualified],
            self::Scheduled => [self::Assessed, self::Absent, self::Eligible, self::Disqualified, self::Scheduled],
            self::Assessed => [self::Ranked, self::Scheduled, self::Disqualified, self::NotEligible],
            self::Absent => [self::Scheduled, self::Ranked, self::Assessed],
            self::Disqualified => [self::Ranked],
            self::Ranked => [self::Assessed],
        };
    }

    public function inSelection(): bool
    {
        return in_array($this, [self::Eligible, self::Scheduled, self::Assessed, self::Absent, self::Ranked], true);
    }
}
