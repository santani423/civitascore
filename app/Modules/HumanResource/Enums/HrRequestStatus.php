<?php

namespace Modules\HumanResource\Enums;

enum HrRequestStatus: string implements HasLabel
{
    case Pending = 'pending';
    /** Dikembalikan ke pemohon untuk direvisi, lalu dapat diajukan ulang. */
    case Returned = 'returned';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu',
            self::Returned => 'Dikembalikan',
            self::Approved => 'Disetujui',
            self::Rejected => 'Ditolak',
            self::Cancelled => 'Dibatalkan',
        };
    }
}
