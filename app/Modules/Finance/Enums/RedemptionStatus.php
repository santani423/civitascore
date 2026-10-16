<?php

namespace Modules\Finance\Enums;

/** Status penukaran voucher. */
enum RedemptionStatus: string
{
    case Applied = 'applied';
    case Reversed = 'reversed';

    public function label(): string
    {
        return match ($this) {
            self::Applied => 'Diterapkan',
            self::Reversed => 'Dibatalkan',
        };
    }

    /** @return array<int, array{value: string, label: string}> */
    public static function options(): array
    {
        return array_map(fn (self $case) => ['value' => $case->value, 'label' => $case->label()], self::cases());
    }
}
