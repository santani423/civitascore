<?php

namespace Modules\ApprovalWorkflow\Enums;

enum ApprovalHistoryEvent: string
{
    case Submitted = 'submitted';
    case StepAdvanced = 'step_advanced';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case ReturnedToPreviousStep = 'returned_to_previous_step';
}
