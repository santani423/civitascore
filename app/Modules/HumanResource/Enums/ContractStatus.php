<?php

namespace Modules\HumanResource\Enums;

enum ContractStatus: string implements HasLabel
{
    case Active = 'active';
    case Expired = 'expired';
    case Terminated = 'terminated';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Aktif',
            self::Expired => 'Berakhir',
            self::Terminated => 'Diakhiri',
        };
    }
}
