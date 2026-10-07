<?php

namespace Modules\ApprovalWorkflow\Enums;

enum ApprovalRequestStepStatus: string
{
    case Pending = 'pending';
    case InReview = 'in_review';
    case Approved = 'approved';
    case Rejected = 'rejected';
    /** Langkah tempat pengajuan dikembalikan ke pemohon. */
    case Returned = 'returned';
    case Skipped = 'skipped';
}
