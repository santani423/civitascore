<?php

namespace Modules\HumanResource\Enums;

enum EducationLevel: string implements HasLabel
{
    case HighSchool = 'sma';
    case D1 = 'd1';
    case D2 = 'd2';
    case D3 = 'd3';
    case D4 = 'd4';
    case S1 = 's1';
    case Profession = 'profesi';
    case S2 = 's2';
    case Specialist = 'spesialis';
    case S3 = 's3';

    public function label(): string
    {
        return match ($this) {
            self::HighSchool => 'SMA/SMK',
            self::D1 => 'D1',
            self::D2 => 'D2',
            self::D3 => 'D3',
            self::D4 => 'D4',
            self::S1 => 'S1',
            self::Profession => 'Profesi',
            self::S2 => 'S2',
            self::Specialist => 'Spesialis',
            self::S3 => 'S3',
        };
    }

    /**
     * Urutan jenjang — dipakai untuk menurunkan "pendidikan terakhir"
     * pegawai dari seluruh riwayat pendidikannya (nilai terbesar menang),
     * bukan dari urutan input.
     */
    public function rank(): int
    {
        return match ($this) {
            self::HighSchool => 1,
            self::D1 => 2,
            self::D2 => 3,
            self::D3 => 4,
            self::D4 => 5,
            self::S1 => 6,
            self::Profession => 7,
            self::S2 => 8,
            self::Specialist => 9,
            self::S3 => 10,
        };
    }
}
