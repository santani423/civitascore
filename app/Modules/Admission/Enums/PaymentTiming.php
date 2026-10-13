<?php

namespace Modules\Admission\Enums;

enum PaymentTiming: string
{
    case BeforeSubmit = 'before_submit';
    case AfterSubmit = 'after_submit';

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
