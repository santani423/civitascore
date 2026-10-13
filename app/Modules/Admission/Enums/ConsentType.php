<?php

namespace Modules\Admission\Enums;

enum ConsentType: string
{
    case PrivacyPolicy = 'privacy_policy';
    case Terms = 'terms';
    case DataProcessingPmb = 'data_processing_pmb';
    case MarketingOptional = 'marketing_optional';
    case GuardianConsent = 'guardian_consent';
    case Declaration = 'declaration';
    case AcademicTerms = 'academic_terms';

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
