<?php

namespace Modules\Finance\Enums;

/** Status persetujuan maker-checker yang dipakai bersama (penyesuaian, award, refund). */
enum ApprovalStatus: string
{
    case PendingApproval = 'pending_approval';
    case Applied = 'applied';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::PendingApproval => 'Menunggu Persetujuan',
            self::Applied => 'Diterapkan',
            self::Rejected => 'Ditolak',
            self::Cancelled => 'Dibatalkan',
        };
    }

    /** @return array<int, array{value: string, label: string}> */
    public static function options(): array
    {
        return array_map(fn (self $case) => ['value' => $case->value, 'label' => $case->label()], self::cases());
    }
}
