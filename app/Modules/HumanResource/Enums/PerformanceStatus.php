<?php

namespace Modules\HumanResource\Enums;

enum PerformanceStatus: string implements HasLabel
{
    case Draft = 'draft';
    case Final = 'final';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Final => 'Final',
        };
    }
}
