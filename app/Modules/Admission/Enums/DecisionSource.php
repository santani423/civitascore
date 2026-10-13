<?php

namespace Modules\Admission\Enums;

enum DecisionSource: string
{
    case SelectionRun = 'selection_run';
    case WaitlistPromotion = 'waitlist_promotion';
    case Correction = 'correction';
    case OfferWithdrawal = 'offer_withdrawal';

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
