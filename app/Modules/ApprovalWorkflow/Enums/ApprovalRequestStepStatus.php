<?php

namespace Modules\ApprovalWorkflow\Enums;

enum ApprovalRequestStepStatus: string
{
    case Pending = 'pending';
    case InReview = 'in_review';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Skipped = 'skipped';
}
