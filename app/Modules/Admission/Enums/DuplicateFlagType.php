<?php

namespace Modules\Admission\Enums;

enum DuplicateFlagType: string
{
    case IdentityConflict = 'identity_conflict';
    case HeuristicMatch = 'heuristic_match';
    case IdentityClaim = 'identity_claim';

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
