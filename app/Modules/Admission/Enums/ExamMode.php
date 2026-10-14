<?php

namespace Modules\Admission\Enums;

enum ExamMode: string
{
    case Onsite = 'onsite';
    case OnlineProctored = 'online_proctored';
    case Cbt = 'cbt';
    case Interview = 'interview';
    case PortfolioReview = 'portfolio_review';

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
