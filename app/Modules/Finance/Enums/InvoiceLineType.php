<?php

namespace Modules\Finance\Enums;

/** Jenis baris tagihan; potongan bernilai negatif. */
enum InvoiceLineType: string
{
    case Charge = 'charge';
    case Aid = 'aid';
    case Discount = 'discount';
    case Subsidy = 'subsidy';
    case Voucher = 'voucher';
    case Waiver = 'waiver';
    case Penalty = 'penalty';
    case Adjustment = 'adjustment';

    public function label(): string
    {
        return match ($this) {
            self::Charge => 'Biaya',
            self::Aid => 'Beasiswa/Bantuan',
            self::Discount => 'Potongan',
            self::Subsidy => 'Subsidi',
            self::Voucher => 'Voucher',
            self::Waiver => 'Pembebasan',
            self::Penalty => 'Denda',
            self::Adjustment => 'Penyesuaian',
        };
    }

    /** @return array<int, array{value: string, label: string}> */
    public static function options(): array
    {
        return array_map(fn (self $case) => ['value' => $case->value, 'label' => $case->label()], self::cases());
    }
}
