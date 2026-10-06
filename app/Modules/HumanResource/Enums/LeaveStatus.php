<?php

namespace Modules\HumanResource\Enums;

enum LeaveStatus: string implements HasLabel
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    /** Dikembalikan ke pemohon untuk direvisi. */
    case Returned = 'returned';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Submitted => 'Diajukan',
            self::Returned => 'Dikembalikan',
            self::Approved => 'Disetujui',
            self::Rejected => 'Ditolak',
            self::Cancelled => 'Dibatalkan',
        };
    }
}
