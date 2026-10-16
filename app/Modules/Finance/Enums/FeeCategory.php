<?php

namespace Modules\Finance\Enums;

/** Kategori komponen biaya (FR-CFG-01). */
enum FeeCategory: string
{
    case TuitionFixed = 'tuition_fixed';
    case Ukt = 'ukt';
    case Development = 'development';
    case CreditFee = 'credit_fee';
    case Practicum = 'practicum';
    case Exam = 'exam';
    case Thesis = 'thesis';
    case Graduation = 'graduation';
    case Leave = 'leave';
    case Administrative = 'administrative';
    case Penalty = 'penalty';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::TuitionFixed => 'SPP/BPP Tetap',
            self::Ukt => 'UKT',
            self::Development => 'DPP/Uang Pangkal',
            self::CreditFee => 'Biaya per SKS',
            self::Practicum => 'Praktikum',
            self::Exam => 'Ujian',
            self::Thesis => 'Tugas Akhir',
            self::Graduation => 'Wisuda',
            self::Leave => 'Biaya Cuti',
            self::Administrative => 'Administrasi',
            self::Penalty => 'Denda',
            self::Other => 'Lainnya',
        };
    }

    /** @return array<int, array{value: string, label: string}> */
    public static function options(): array
    {
        return array_map(fn (self $case) => ['value' => $case->value, 'label' => $case->label()], self::cases());
    }
}
