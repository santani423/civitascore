<?php

namespace Modules\Finance\Enums;

/** Jenis keringanan (rancangan KEU §1.3). */
enum AidKind: string
{
    case Scholarship = 'scholarship';
    case Discount = 'discount';
    case Subsidy = 'subsidy';
    case Kip = 'kip';

    public function label(): string
    {
        return match ($this) {
            self::Scholarship => 'Beasiswa',
            self::Discount => 'Potongan',
            self::Subsidy => 'Subsidi',
            self::Kip => 'KIP Kuliah',
        };
    }

    /** @return array<int, array{value: string, label: string}> */
    public static function options(): array
    {
        return array_map(fn (self $case) => ['value' => $case->value, 'label' => $case->label()], self::cases());
    }
}
