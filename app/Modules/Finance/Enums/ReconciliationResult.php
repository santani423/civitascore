<?php

namespace Modules\Finance\Enums;

/** Hasil pencocokan per baris (§8.2.7). */
enum ReconciliationResult: string
{
    case Matched = 'matched';
    case MissingInSystem = 'missing_in_system';
    case MissingInStatement = 'missing_in_statement';
    case AmountMismatch = 'amount_mismatch';
    case Duplicate = 'duplicate';
    case Unidentified = 'unidentified';

    public function label(): string
    {
        return match ($this) {
            self::Matched => 'Cocok',
            self::MissingInSystem => 'Tidak Ada di Sistem',
            self::MissingInStatement => 'Tidak Ada di Mutasi',
            self::AmountMismatch => 'Nominal Berbeda',
            self::Duplicate => 'Ganda',
            self::Unidentified => 'Tidak Teridentifikasi',
        };
    }

    /** @return array<int, array{value: string, label: string}> */
    public static function options(): array
    {
        return array_map(fn (self $case) => ['value' => $case->value, 'label' => $case->label()], self::cases());
    }
}
