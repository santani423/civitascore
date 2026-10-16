<?php

namespace Modules\Finance\Enums;

/** Status rekonsiliasi transaksi (lapis 3, §8.2.7). */
enum ReconciliationStatus: string
{
    case NotRequired = 'not_required';
    case Unreconciled = 'unreconciled';
    case PendingDeposit = 'pending_deposit';
    case Matched = 'matched';
    case Exception = 'exception';
    case Resolved = 'resolved';

    public function label(): string
    {
        return match ($this) {
            self::NotRequired => 'Tidak Perlu',
            self::Unreconciled => 'Belum Direkonsiliasi',
            self::PendingDeposit => 'Menunggu Setoran',
            self::Matched => 'Cocok',
            self::Exception => 'Pengecualian',
            self::Resolved => 'Diselesaikan',
        };
    }

    /** @return array<int, array{value: string, label: string}> */
    public static function options(): array
    {
        return array_map(fn (self $case) => ['value' => $case->value, 'label' => $case->label()], self::cases());
    }
}
