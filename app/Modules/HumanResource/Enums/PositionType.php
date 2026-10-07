<?php

namespace Modules\HumanResource\Enums;

enum PositionType: string implements HasLabel
{
    case Structural = 'structural';
    case Functional = 'functional';

    public function label(): string
    {
        return match ($this) {
            self::Structural => 'Struktural',
            self::Functional => 'Fungsional',
        };
    }
}
