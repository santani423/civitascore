<?php

namespace Modules\HumanResource\Enums;

enum DocumentType: string implements HasLabel
{
    case Ktp = 'ktp';
    case Kk = 'kk';
    case Diploma = 'ijazah';
    case Transcript = 'transkrip';
    case AppointmentDecree = 'sk_pengangkatan';
    case PositionDecree = 'sk_jabatan';
    case RankDecree = 'sk_pangkat';
    case Contract = 'kontrak';
    case Certificate = 'sertifikat';
    case Other = 'lainnya';

    public function label(): string
    {
        return match ($this) {
            self::Ktp => 'KTP',
            self::Kk => 'Kartu Keluarga',
            self::Diploma => 'Ijazah',
            self::Transcript => 'Transkrip',
            self::AppointmentDecree => 'SK Pengangkatan',
            self::PositionDecree => 'SK Jabatan',
            self::RankDecree => 'SK Pangkat',
            self::Contract => 'Kontrak',
            self::Certificate => 'Sertifikat',
            self::Other => 'Dokumen Lainnya',
        };
    }
}
