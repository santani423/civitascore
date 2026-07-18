<?php

namespace Modules\UserManagement\Enums;

enum PermissionScope: string
{
    case Menu = 'menu';
    case Module = 'module';
    case Endpoint = 'endpoint';
    case Data = 'data';
}
