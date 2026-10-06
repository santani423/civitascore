<?php

namespace Modules\HumanResource\Enums;

enum EmployeeType: string implements HasLabel
{
    case Lecturer = 'lecturer';
    case Staff = 'staff';

    public function label(): string
    {
        return match ($this) {
            self::Lecturer => 'Dosen',
            self::Staff => 'Tenaga Kependidikan',
        };
    }
}
