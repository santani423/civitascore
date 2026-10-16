<?php

namespace Modules\Finance\Enums;

/** Status voucher; kedaluwarsa/habis dihitung dari masa berlaku & kuota. */
enum VoucherStatus: string
{
    case Active = 'active';
    case Paused = 'paused';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Aktif',
            self::Paused => 'Dijeda',
            self::Archived => 'Diarsipkan',
        };
    }

    /** @return array<int, array{value: string, label: string}> */
    public static function options(): array
    {
        return array_map(fn (self $case) => ['value' => $case->value, 'label' => $case->label()], self::cases());
    }
}
