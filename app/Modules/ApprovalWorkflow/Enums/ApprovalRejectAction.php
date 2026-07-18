<?php

namespace Modules\ApprovalWorkflow\Enums;

enum ApprovalRejectAction: string
{
    case StopWorkflow = 'stop_workflow';
    case ReturnToPreviousStep = 'return_to_previous_step';
}
