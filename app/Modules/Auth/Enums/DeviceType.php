<?php

namespace Modules\Auth\Enums;

enum DeviceType: string
{
    case Web = 'web';
    case Mobile = 'mobile';
    case Desktop = 'desktop';
    case Unknown = 'unknown';
}
