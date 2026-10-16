<?php

namespace Modules\Finance\Enums;

/** Status penetapan bantuan (§8.2.11). */
enum AidAwardStatus: string
{
    case PendingApproval = 'pending_approval';
    case Active = 'active';
    case Rejected = 'rejected';
    case Revoked = 'revoked';

    public function label(): string
    {
        return match ($this) {
            self::PendingApproval => 'Menunggu Persetujuan',
            self::Active => 'Aktif',
            self::Rejected => 'Ditolak',
            self::Revoked => 'Dicabut',
        };
    }

    /** @return array<int, array{value: string, label: string}> */
    public static function options(): array
    {
        return array_map(fn (self $case) => ['value' => $case->value, 'label' => $case->label()], self::cases());
    }
}
