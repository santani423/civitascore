<?php

namespace Modules\Admission\Enums;

enum TemplateStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Retired = 'retired';

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
