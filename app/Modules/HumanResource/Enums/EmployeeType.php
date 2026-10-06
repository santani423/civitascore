<?php

namespace Modules\HumanResource\Enums;

enum EmployeeType: string
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
