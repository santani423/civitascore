<?php

namespace Modules\Academic\Enums;

/** Status cekal (docs/academic-learning/01-siakad.md A.8.5). */
enum AcademicBlockStatus: string
{
    case Active = 'active';
    case Exempted = 'exempted';
    case Revoked = 'revoked';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Aktif',
            self::Exempted => 'Berdispensasi',
            self::Revoked => 'Dicabut',
        };
    }
}
