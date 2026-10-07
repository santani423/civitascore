<?php

namespace Modules\ApprovalWorkflow\Enums;

enum ApprovalRequestStatus: string
{
    case Submitted = 'submitted';
    case InProgress = 'in_progress';
    /** Dikembalikan ke pemohon untuk direvisi, lalu dapat diajukan ulang. */
    case Returned = 'returned';
    case Approved = 'approved';
    case Rejected = 'rejected';
}
