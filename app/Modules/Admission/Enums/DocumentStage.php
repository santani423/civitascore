<?php

namespace Modules\Admission\Enums;

enum DocumentStage: string
{
    case Application = 'application';
    case ReRegistration = 're_registration';

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
