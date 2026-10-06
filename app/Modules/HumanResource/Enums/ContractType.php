<?php

namespace Modules\HumanResource\Enums;

enum ContractType: string implements HasLabel
{
    case Pkwt = 'pkwt';
    case Pkwtt = 'pkwtt';
    case Honorary = 'honorary';
    case Outsourcing = 'outsourcing';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Pkwt => 'PKWT (Waktu Tertentu)',
            self::Pkwtt => 'PKWTT (Waktu Tidak Tertentu)',
            self::Honorary => 'Honorer',
            self::Outsourcing => 'Outsourcing',
            self::Other => 'Lainnya',
        };
    }
}
