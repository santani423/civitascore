<?php

namespace Modules\Finance\Enums;

/** Satuan periode denda. */
enum PenaltyPeriodUnit: string
{
    case Once = 'once';
    case Day = 'day';
    case Month = 'month';

    public function label(): string
    {
        return match ($this) {
            self::Once => 'Sekali',
            self::Day => 'Per Hari',
            self::Month => 'Per Bulan',
        };
    }

    /** @return array<int, array{value: string, label: string}> */
    public static function options(): array
    {
        return array_map(fn (self $case) => ['value' => $case->value, 'label' => $case->label()], self::cases());
    }
}
