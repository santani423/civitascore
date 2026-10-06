<?php

namespace Modules\ApprovalWorkflow\Enums;

enum ApprovalActionType: string
{
    case Approve = 'approve';
    case Reject = 'reject';
    case Delegate = 'delegate';
    /** Dikembalikan ke pemohon untuk direvisi (catatan wajib). */
    case Return = 'return';
}
