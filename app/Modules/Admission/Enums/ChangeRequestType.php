<?php

namespace Modules\Admission\Enums;

enum ChangeRequestType: string
{
    case ScoreCorrection = 'score_correction';
    case ResultCorrection = 'result_correction';
    case ReRegistrationCancellation = 're_registration_cancellation';
    case BulkExtension = 'bulk_extension';
    case PublicationAcceleration = 'publication_acceleration';
    case Refund = 'refund';
    case BulkRefund = 'bulk_refund';

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
