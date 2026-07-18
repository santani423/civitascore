<?php

namespace Modules\Auth\Enums;

enum LoginHistoryStatus: string
{
    case Success = 'success';
    case Failed = 'failed';
    case Blocked = 'blocked';
}
