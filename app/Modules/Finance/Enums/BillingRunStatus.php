<?php

namespace Modules\Finance\Enums;

/** Status penerbitan tagihan massal (§8.2.10). */
enum BillingRunStatus: string
{
    case Simulated = 'simulated';
    case PendingApproval = 'pending_approval';
    case Posted = 'posted';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Simulated => 'Simulasi',
            self::PendingApproval => 'Menunggu Persetujuan',
            self::Posted => 'Diterbitkan',
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
