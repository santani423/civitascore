<?php

namespace Modules\Finance\Enums;

/** Jenis hitung denda. */
enum PenaltyCalcType: string
{
    case Flat = 'flat';
    case Percent = 'percent';

    public function label(): string
    {
        return match ($this) {
            self::Flat => 'Nominal Tetap',
            self::Percent => 'Persentase',
        };
    }

    /** @return array<int, array{value: string, label: string}> */
    public static function options(): array
    {
        return array_map(fn (self $case) => ['value' => $case->value, 'label' => $case->label()], self::cases());
    }
}
