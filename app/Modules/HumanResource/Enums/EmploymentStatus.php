<?php

namespace Modules\HumanResource\Enums;

enum EmploymentStatus: string implements HasLabel
{
    case Permanent = 'permanent';
    case Contract = 'contract';
    case Honorary = 'honorary';
    case Probation = 'probation';
    case Outsourcing = 'outsourcing';

    public function label(): string
    {
        return match ($this) {
            self::Permanent => 'Tetap',
            self::Contract => 'Kontrak',
            self::Honorary => 'Honorer',
            self::Probation => 'Masa Percobaan',
            self::Outsourcing => 'Outsourcing',
        };
    }
}
