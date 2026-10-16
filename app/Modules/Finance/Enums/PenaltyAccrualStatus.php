<?php

namespace Modules\Finance\Enums;

/** Status denda berjalan (§8.2.13). */
enum PenaltyAccrualStatus: string
{
    case Accruing = 'accruing';
    case Crystallized = 'crystallized';
    case Waived = 'waived';
    case Stopped = 'stopped';

    public function label(): string
    {
        return match ($this) {
            self::Accruing => 'Berjalan',
            self::Crystallized => 'Ditagihkan',
            self::Waived => 'Dibebaskan',
            self::Stopped => 'Dihentikan',
        };
    }

    /** @return array<int, array{value: string, label: string}> */
    public static function options(): array
    {
        return array_map(fn (self $case) => ['value' => $case->value, 'label' => $case->label()], self::cases());
    }
}
