<?php

namespace Modules\Finance\Enums;

/** Jenis mutasi ledger deposit. */
enum DepositEntryType: string
{
    case Overpayment = 'overpayment';
    case TopUp = 'top_up';
    case AdjustmentCredit = 'adjustment_credit';
    case Allocation = 'allocation';
    case RefundHold = 'refund_hold';
    case RefundRelease = 'refund_release';
    case RefundPayout = 'refund_payout';
    case Reversal = 'reversal';

    public function label(): string
    {
        return match ($this) {
            self::Overpayment => 'Kelebihan Bayar',
            self::TopUp => 'Titipan',
            self::AdjustmentCredit => 'Kredit Penyesuaian',
            self::Allocation => 'Dipakai untuk Tagihan',
            self::RefundHold => 'Ditahan untuk Refund',
            self::RefundRelease => 'Penahanan Dilepas',
            self::RefundPayout => 'Dikembalikan',
            self::Reversal => 'Pembalikan',
        };
    }

    /** @return array<int, array{value: string, label: string}> */
    public static function options(): array
    {
        return array_map(fn (self $case) => ['value' => $case->value, 'label' => $case->label()], self::cases());
    }
}
