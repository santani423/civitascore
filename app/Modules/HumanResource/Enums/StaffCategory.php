<?php

namespace Modules\HumanResource\Enums;

enum StaffCategory: string implements HasLabel
{
    case Administration = 'administration';
    case Laboratory = 'laboratory';
    case Technician = 'technician';
    case Librarian = 'librarian';
    case Archivist = 'archivist';
    case InformationTechnology = 'it';
    case Finance = 'finance';
    case Security = 'security';
    case Driver = 'driver';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Administration => 'Staf Administrasi',
            self::Laboratory => 'Laboran',
            self::Technician => 'Teknisi',
            self::Librarian => 'Pustakawan',
            self::Archivist => 'Arsiparis',
            self::InformationTechnology => 'Pranata Komputer/TI',
            self::Finance => 'Keuangan',
            self::Security => 'Keamanan',
            self::Driver => 'Pengemudi',
            self::Other => 'Lainnya',
        };
    }
}
