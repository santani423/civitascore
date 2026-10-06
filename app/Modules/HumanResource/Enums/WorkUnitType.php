<?php

namespace Modules\HumanResource\Enums;

enum WorkUnitType: string implements HasLabel
{
    case Rectorate = 'rectorate';
    case Faculty = 'faculty';
    case StudyProgram = 'study_program';
    case Bureau = 'bureau';
    case Unit = 'unit';
    case Laboratory = 'laboratory';
    case Library = 'library';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Rectorate => 'Rektorat',
            self::Faculty => 'Fakultas',
            self::StudyProgram => 'Program Studi',
            self::Bureau => 'Biro',
            self::Unit => 'Unit/Lembaga',
            self::Laboratory => 'Laboratorium',
            self::Library => 'Perpustakaan',
            self::Other => 'Lainnya',
        };
    }
}
