<?php

namespace Modules\Notification\Enums;

enum NotificationLogStatus: string
{
    case Sending = 'sending';
    case Sent = 'sent';
    case Failed = 'failed';
    case Skipped = 'skipped';
}
