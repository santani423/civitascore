<?php

namespace Modules\ApprovalWorkflow\Enums;

enum ApprovalApproverType: string
{
    case Role = 'role';
    case User = 'user';
    case Position = 'position';
}
