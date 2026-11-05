<?php

namespace Modules\Admission\Enums;

/** Domain asesmen One Atma Talent (docs/one-atma-talent/01 §3). Hanya Interest yang sudah punya scoring engine. */
enum AssessmentDomain: string
{
    case Interest = 'interest';
    case Aptitude = 'aptitude';
    case AcademicReadiness = 'academic_readiness';
    case LearningPreference = 'learning_preference';
    case CareerAspiration = 'career_aspiration';

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /** Domain yang sudah memiliki scoring engine dan boleh dipublikasikan. */
    public function supportsScoring(): bool
    {
        return $this === self::Interest;
    }
}
