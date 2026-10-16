<?php

namespace Modules\Finance\Enums;

/** Kelompok tagihan — cicilan & cekal bekerja per kelompok (rancangan KEU F-03). */
enum BillGroup: string
{
    case Registration = 'registration';
    case Credit = 'credit';
    case Incidental = 'incidental';
    case Penalty = 'penalty';
    case Admission = 'admission';
    case ShortTerm = 'short_term';
    case Graduation = 'graduation';
    case OpeningBalance = 'opening_balance';

    public function label(): string
    {
        return match ($this) {
            self::Registration => 'Registrasi Semester',
            self::Credit => 'Biaya SKS',
            self::Incidental => 'Insidental',
            self::Penalty => 'Denda',
            self::Admission => 'Biaya Awal (PMB)',
            self::ShortTerm => 'Semester Pendek',
            self::Graduation => 'Wisuda',
            self::OpeningBalance => 'Saldo Awal',
        };
    }

    /** @return array<int, array{value: string, label: string}> */
    public static function options(): array
    {
        return array_map(fn (self $case) => ['value' => $case->value, 'label' => $case->label()], self::cases());
    }
}
