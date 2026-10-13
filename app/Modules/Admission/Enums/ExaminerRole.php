<?php

namespace Modules\Admission\Enums;

enum ExaminerRole: string
{
    case Proctor = 'proctor';
    case Examiner = 'examiner';
    case Interviewer = 'interviewer';

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
