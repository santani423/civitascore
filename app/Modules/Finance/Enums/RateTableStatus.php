<?php

namespace Modules\Finance\Enums;

/** Status tabel tarif (BR-KEU-01). */
enum RateTableStatus: string
{
    case Draft = 'draft';
    case PendingApproval = 'pending_approval';
    case Published = 'published';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draf',
            self::PendingApproval => 'Menunggu Persetujuan',
            self::Published => 'Berlaku',
            self::Archived => 'Diarsipkan',
        };
    }

    /** @return array<int, array{value: string, label: string}> */
    public static function options(): array
    {
        return array_map(fn (self $case) => ['value' => $case->value, 'label' => $case->label()], self::cases());
    }
}
