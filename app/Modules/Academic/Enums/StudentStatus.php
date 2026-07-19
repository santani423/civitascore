<?php

namespace Modules\Academic\Enums;

enum StudentStatus: string
{
    case Active = 'active';
    case Leave = 'leave';
    case Graduated = 'graduated';
    case Inactive = 'inactive';
    case DroppedOut = 'dropped_out';
}
