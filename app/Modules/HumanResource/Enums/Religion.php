<?php

namespace Modules\HumanResource\Enums;

enum Religion: string implements HasLabel
{
    case Islam = 'islam';
    case Protestant = 'protestant';
    case Catholic = 'catholic';
    case Hindu = 'hindu';
    case Buddhist = 'buddhist';
    case Confucian = 'confucian';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Islam => 'Islam',
            self::Protestant => 'Kristen Protestan',
            self::Catholic => 'Katolik',
            self::Hindu => 'Hindu',
            self::Buddhist => 'Buddha',
            self::Confucian => 'Konghucu',
            self::Other => 'Lainnya',
        };
    }
}
