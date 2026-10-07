<?php

namespace Modules\Academic\Enums;

enum StudentRequestType: string
{
    case Leave = 'leave';
    case Reactivation = 'reactivation';
    case DataChange = 'data_change';
    case Letter = 'letter';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Leave => 'Cuti Akademik',
            self::Reactivation => 'Aktif Kembali',
            self::DataChange => 'Perubahan Data',
            self::Letter => 'Surat Keterangan',
            self::Other => 'Pengajuan Lainnya',
        };
    }
}
