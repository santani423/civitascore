<?php

namespace Modules\Admission\Enums;

enum Lifecycle: string
{
    case Form = 'form';
    case Verification = 'verification';
    case Selection = 'selection';
    case Decision = 'decision';
    case ReRegistration = 're_registration';
    case Conversion = 'conversion';
    case RegistrationPayment = 'registration_payment';
    case Document = 'document';

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
