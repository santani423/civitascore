<?php

namespace Modules\Finance\Enums;

/** Status pengembalian dana (§8.2.6). */
enum RefundStatus: string
{
    case PendingApproval = 'pending_approval';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Completed = 'completed';
    case Failed = 'failed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::PendingApproval => 'Menunggu Persetujuan',
            self::Approved => 'Disetujui',
            self::Rejected => 'Ditolak',
            self::Completed => 'Selesai',
            self::Failed => 'Gagal',
            self::Cancelled => 'Dibatalkan',
        };
    }

    /** @return array<int, array{value: string, label: string}> */
    public static function options(): array
    {
        return array_map(fn (self $case) => ['value' => $case->value, 'label' => $case->label()], self::cases());
    }
}
