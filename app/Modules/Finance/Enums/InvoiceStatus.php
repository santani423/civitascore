<?php

namespace Modules\Finance\Enums;

/** Status tagihan (lapis 1, rancangan KEU §8.2.3). Nilai lama unpaid/partial/paid dipertahankan. */
enum InvoiceStatus: string
{
    case Draft = 'draft';
    case Unpaid = 'unpaid';
    case Partial = 'partial';
    case Paid = 'paid';
    case Cancelled = 'cancelled';
    case Waived = 'waived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draf',
            self::Unpaid => 'Belum Dibayar',
            self::Partial => 'Dibayar Sebagian',
            self::Paid => 'Lunas',
            self::Cancelled => 'Dibatalkan',
            self::Waived => 'Dibebaskan',
        };
    }

    /** Tagihan yang masih menjadi piutang (belum lunas & masih berlaku). */
    public function isOpen(): bool
    {
        return $this === self::Unpaid || $this === self::Partial;
    }

    /** @return array<int, self> */
    public static function open(): array
    {
        return [self::Unpaid, self::Partial];
    }

    /** @return array<int, array{value: string, label: string}> */
    public static function options(): array
    {
        return array_map(fn (self $case) => ['value' => $case->value, 'label' => $case->label()], self::cases());
    }
}
