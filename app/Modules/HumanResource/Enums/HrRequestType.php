<?php

namespace Modules\HumanResource\Enums;

enum HrRequestType: string implements HasLabel
{
    case Leave = 'leave';
    case Transfer = 'transfer';
    case DataChange = 'data_change';
    case Promotion = 'promotion';
    case Document = 'document';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Leave => 'Pengajuan Cuti',
            self::Transfer => 'Pengajuan Mutasi',
            self::DataChange => 'Pengajuan Perubahan Data',
            self::Promotion => 'Pengajuan Kenaikan Jabatan',
            self::Document => 'Pengajuan Dokumen',
            self::Other => 'Pengajuan Lainnya',
        };
    }
}
