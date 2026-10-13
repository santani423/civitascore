<?php

namespace Modules\Admission\Enums;

enum ComponentSource: string
{
    case Administrative = 'administrative';
    case ReportCard = 'report_card';
    case Achievement = 'achievement';
    case WrittenExam = 'written_exam';
    case Cbt = 'cbt';
    case Interview = 'interview';
    case Portfolio = 'portfolio';
    case SpecialTest = 'special_test';
    case ScholarshipAssessment = 'scholarship_assessment';

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
