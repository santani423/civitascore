<?php

namespace Modules\Admission\Enums;

/** Status review hasil sesi. `flagged` menahan publikasi sampai ditandai `reviewed`. */
enum AssessmentReviewStatus: string
{
    case None = 'none';
    case Flagged = 'flagged';
    case Reviewed = 'reviewed';

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
