<?php

namespace Modules\Finance\Enums;

/** Status sesi kas (§8.2.12). */
enum CashierSessionStatus: string
{
    case Open = 'open';
    case Closed = 'closed';
    case Verified = 'verified';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Dibuka',
            self::Closed => 'Ditutup',
            self::Verified => 'Setoran Terverifikasi',
        };
    }

    /** @return array<int, array{value: string, label: string}> */
    public static function options(): array
    {
        return array_map(fn (self $case) => ['value' => $case->value, 'label' => $case->label()], self::cases());
    }
}
