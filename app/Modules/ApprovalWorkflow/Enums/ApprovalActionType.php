<?php

namespace Modules\ApprovalWorkflow\Enums;

enum ApprovalActionType: string
{
    case Approve = 'approve';
    case Reject = 'reject';
    case Delegate = 'delegate';
}
