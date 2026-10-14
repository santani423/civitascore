<?php

namespace Modules\Admission\Enums;

enum InvoiceType: string
{
    case RegistrationFee = 'registration_fee';
    case InitialTuition = 'initial_tuition';

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
