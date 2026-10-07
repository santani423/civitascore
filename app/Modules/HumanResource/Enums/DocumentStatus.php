<?php

namespace Modules\HumanResource\Enums;

enum DocumentStatus: string implements HasLabel
{
    case Pending = 'pending';
    case Valid = 'valid';
    case Invalid = 'invalid';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu Verifikasi',
            self::Valid => 'Valid',
            self::Invalid => 'Tidak Valid',
        };
    }
}
