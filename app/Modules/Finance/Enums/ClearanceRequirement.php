<?php

namespace Modules\Finance\Enums;

/** Syarat kelulusan dari cekal keuangan (§10.3). */
enum ClearanceRequirement: string
{
    case FullyPaid = 'fully_paid';
    case DueItemsPaid = 'due_items_paid';
    case FirstInstallmentPaid = 'first_installment_paid';

    public function label(): string
    {
        return match ($this) {
            self::FullyPaid => 'Lunas',
            self::DueItemsPaid => 'Kewajiban Jatuh Tempo Lunas',
            self::FirstInstallmentPaid => 'Termin Pertama Lunas',
        };
    }

    /** @return array<int, array{value: string, label: string}> */
    public static function options(): array
    {
        return array_map(fn (self $case) => ['value' => $case->value, 'label' => $case->label()], self::cases());
    }
}
