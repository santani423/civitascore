<?php

namespace Modules\Academic\Enums;

enum AcademicCalendarCategory: string
{
    case Registration = 'registration';
    case Krs = 'krs';
    case Lecture = 'lecture';
    case MidtermExam = 'midterm_exam';
    case FinalExam = 'final_exam';
    case Holiday = 'holiday';
    case Graduation = 'graduation';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Registration => 'Registrasi',
            self::Krs => 'KRS',
            self::Lecture => 'Perkuliahan',
            self::MidtermExam => 'UTS',
            self::FinalExam => 'UAS',
            self::Holiday => 'Libur',
            self::Graduation => 'Wisuda',
            self::Other => 'Lainnya',
        };
    }
}
