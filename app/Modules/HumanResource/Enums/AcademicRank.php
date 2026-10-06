<?php

namespace Modules\HumanResource\Enums;

enum AcademicRank: string implements HasLabel
{
    case TeachingStaff = 'tenaga_pengajar';
    case AssistantExpert = 'asisten_ahli';
    case Lecturer = 'lektor';
    case HeadLecturer = 'lektor_kepala';
    case Professor = 'profesor';

    public function label(): string
    {
        return match ($this) {
            self::TeachingStaff => 'Tenaga Pengajar',
            self::AssistantExpert => 'Asisten Ahli',
            self::Lecturer => 'Lektor',
            self::HeadLecturer => 'Lektor Kepala',
            self::Professor => 'Profesor',
        };
    }
}
