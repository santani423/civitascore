<?php

namespace Modules\Academic\Enums;

/** Status dispensasi cekal per layanan. */
enum BlockExemptionStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Rejected = 'rejected';
    case Revoked = 'revoked';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu Persetujuan',
            self::Active => 'Aktif',
            self::Rejected => 'Ditolak',
            self::Revoked => 'Dicabut',
            self::Expired => 'Berakhir',
        };
    }
}
