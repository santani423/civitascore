<?php

namespace Modules\Admission\Enums;

enum RegistrationPaymentStatus: string
{
    case NotRequired = 'not_required';
    case Unpaid = 'unpaid';
    case Pending = 'pending';
    case Paid = 'paid';
    case Expired = 'expired';

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
