<?php

namespace Modules\Admission\Enums;

enum AllocationMethod: string
{
    case ScorePriority = 'score_priority';
    case FirstChoiceOnly = 'first_choice_only';

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
