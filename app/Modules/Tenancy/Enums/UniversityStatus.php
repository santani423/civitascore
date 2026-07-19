<?php

namespace Modules\Tenancy\Enums;

enum UniversityStatus: string
{
    case Draft = 'draft';
    case Trial = 'trial';
    case Active = 'active';
    case Suspended = 'suspended';
    case Expired = 'expired';
    case Terminated = 'terminated';
}
