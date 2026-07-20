<?php

namespace Modules\Scholarship\Enums;

enum ScholarshipApplicationStatus: string
{
    case Submitted = 'submitted';
    case UnderReview = 'under_review';
    case Approved = 'approved';
    case Rejected = 'rejected';
}
