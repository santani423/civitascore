<?php

namespace Modules\Admission\Enums;

enum ContentType: string
{
    case Page = 'page';
    case Faq = 'faq';
    case News = 'news';
    case Guide = 'guide';
    case Brochure = 'brochure';
    case Banner = 'banner';
    case Declaration = 'declaration';
    case PrivacyPolicy = 'privacy_policy';
    case Terms = 'terms';
    case ResultTemplate = 'result_template';
    case LoaTemplate = 'loa_template';
    case OrientationInfo = 'orientation_info';
    case ReRegistrationInfo = 're_registration_info';

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
