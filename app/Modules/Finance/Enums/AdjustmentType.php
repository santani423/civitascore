<?php

namespace Modules\Finance\Enums;

/** Jenis penyesuaian tagihan (§8.2.8). */
enum AdjustmentType: string
{
    case Increase = 'increase';
    case Decrease = 'decrease';
    case Waiver = 'waiver';
    case PenaltyWaiver = 'penalty_waiver';
    case Void = 'void';
    case Voucher = 'voucher';

    public function label(): string
    {
        return match ($this) {
            self::Increase => 'Penambahan',
            self::Decrease => 'Pengurangan',
            self::Waiver => 'Pembebasan',
            self::PenaltyWaiver => 'Pembebasan Denda',
            self::Void => 'Pembatalan Tagihan',
            self::Voucher => 'Voucher',
        };
    }

    /** @return array<int, array{value: string, label: string}> */
    public static function options(): array
    {
        return array_map(fn (self $case) => ['value' => $case->value, 'label' => $case->label()], self::cases());
    }
}
