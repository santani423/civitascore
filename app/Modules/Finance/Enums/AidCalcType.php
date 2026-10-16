<?php

namespace Modules\Finance\Enums;

/** Jenis hitung keringanan. */
enum AidCalcType: string
{
    case Percent = 'percent';
    case Amount = 'amount';
    case Full = 'full';

    public function label(): string
    {
        return match ($this) {
            self::Percent => 'Persentase',
            self::Amount => 'Nominal',
            self::Full => 'Penuh (100%)',
        };
    }

    /** @return array<int, array{value: string, label: string}> */
    public static function options(): array
    {
        return array_map(fn (self $case) => ['value' => $case->value, 'label' => $case->label()], self::cases());
    }
}
