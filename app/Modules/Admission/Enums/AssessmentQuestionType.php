<?php

namespace Modules\Admission\Enums;

/** Jenis butir. Hanya Likert yang didukung scoring RIASEC; jenis lain menunggu scoring engine-nya. */
enum AssessmentQuestionType: string
{
    case Likert = 'likert';

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
