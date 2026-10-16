<?php

namespace Modules\Finance\Enums;

/** Status aturan denda. */
enum PenaltyRuleStatus: string
{
    case Draft = 'draft';
    case PendingApproval = 'pending_approval';
    case Active = 'active';
    case Inactive = 'inactive';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draf',
            self::PendingApproval => 'Menunggu Persetujuan',
            self::Active => 'Aktif',
            self::Inactive => 'Nonaktif',
        };
    }

    /** @return array<int, array{value: string, label: string}> */
    public static function options(): array
    {
        return array_map(fn (self $case) => ['value' => $case->value, 'label' => $case->label()], self::cases());
    }
}
