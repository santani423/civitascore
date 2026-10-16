<?php

namespace Modules\Finance\Enums;

/** Status termin cicilan (§8.2.4); due/overdue adalah turunan. */
enum InstallmentStatus: string
{
    case Scheduled = 'scheduled';
    case Partial = 'partial';
    case Paid = 'paid';
    case Superseded = 'superseded';

    public function label(): string
    {
        return match ($this) {
            self::Scheduled => 'Terjadwal',
            self::Partial => 'Dibayar Sebagian',
            self::Paid => 'Lunas',
            self::Superseded => 'Diganti Jadwal Baru',
        };
    }

    /** @return array<int, array{value: string, label: string}> */
    public static function options(): array
    {
        return array_map(fn (self $case) => ['value' => $case->value, 'label' => $case->label()], self::cases());
    }
}
