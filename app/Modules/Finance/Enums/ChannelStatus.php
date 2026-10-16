<?php

namespace Modules\Finance\Enums;

/** Status kanal pembayaran. */
enum ChannelStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Maintenance = 'maintenance';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Aktif',
            self::Inactive => 'Nonaktif',
            self::Maintenance => 'Pemeliharaan',
        };
    }

    /** @return array<int, array{value: string, label: string}> */
    public static function options(): array
    {
        return array_map(fn (self $case) => ['value' => $case->value, 'label' => $case->label()], self::cases());
    }
}
