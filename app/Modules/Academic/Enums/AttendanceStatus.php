<?php

namespace Modules\Academic\Enums;

enum AttendanceStatus: string
{
    case Present = 'present';
    case Permitted = 'permitted';
    case Sick = 'sick';
    case Absent = 'absent';
}
