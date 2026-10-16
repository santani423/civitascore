<?php

namespace Modules\Finance\Enums;

/** Status transaksi pembayaran (lapis 2, §8.2.5). */
enum TransactionStatus: string
{
    case Created = 'created';
    case Pending = 'pending';
    case AwaitingVerification = 'awaiting_verification';
    case Success = 'success';
    case Failed = 'failed';
    case Expired = 'expired';
    case Cancelled = 'cancelled';
    case Reversed = 'reversed';
    case Refunded = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::Created => 'Dibuat',
            self::Pending => 'Menunggu Pembayaran',
            self::AwaitingVerification => 'Menunggu Verifikasi',
            self::Success => 'Berhasil',
            self::Failed => 'Gagal',
            self::Expired => 'Kedaluwarsa',
            self::Cancelled => 'Dibatalkan',
            self::Reversed => 'Dibalik',
            self::Refunded => 'Dikembalikan',
        };
    }

    /** @return array<int, array{value: string, label: string}> */
    public static function options(): array
    {
        return array_map(fn (self $case) => ['value' => $case->value, 'label' => $case->label()], self::cases());
    }
}
