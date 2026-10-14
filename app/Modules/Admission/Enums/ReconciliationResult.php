<?php

namespace Modules\Admission\Enums;

enum ReconciliationResult: string
{
    case Matched = 'matched';
    case MissingInSystem = 'missing_in_system';
    case MissingInProvider = 'missing_in_provider';
    case AmountMismatch = 'amount_mismatch';
    case DuplicatePayment = 'duplicate_payment';

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
