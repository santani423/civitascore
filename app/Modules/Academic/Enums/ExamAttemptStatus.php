<?php

namespace Modules\Academic\Enums;

enum ExamAttemptStatus: string
{
    case InProgress = 'in_progress';
    case Submitted = 'submitted';
}
