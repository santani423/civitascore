<?php

namespace Modules\ApprovalWorkflow\Enums;

enum ApprovalRequestStatus: string
{
    case Submitted = 'submitted';
    case InProgress = 'in_progress';
    case Approved = 'approved';
    case Rejected = 'rejected';
}
