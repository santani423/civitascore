<?php

namespace Modules\Auth\Enums;

enum LoginFailureReason: string
{
    case InvalidCredentials = 'invalid_credentials';
    case AccountInactive = 'account_inactive';
    case TooManyAttempts = 'too_many_attempts';
}
