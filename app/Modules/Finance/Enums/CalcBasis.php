<?php

namespace Modules\Finance\Enums;

/** Dasar perhitungan baris tarif (§8.3.2). */
enum CalcBasis: string
{
    case Flat = 'flat';
    case PerCredit = 'per_credit';

    public function label(): string
    {
        return match ($this) {
            self::Flat => 'Tetap',
            self::PerCredit => 'Per SKS',
        };
    }

    /** @return array<int, array{value: string, label: string}> */
    public static function options(): array
    {
        return array_map(fn (self $case) => ['value' => $case->value, 'label' => $case->label()], self::cases());
    }
}
