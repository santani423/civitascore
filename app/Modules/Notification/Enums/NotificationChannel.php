<?php

namespace Modules\Notification\Enums;

enum NotificationChannel: string
{
    case Database = 'database';
    case Mail = 'mail';
    case Push = 'push';
    case Whatsapp = 'whatsapp';
    case Sms = 'sms';

    /**
     * Whether TemplatedNotification actually has a working toX() delivery
     * method for this channel. Push/Whatsapp/Sms are scaffolded (enum +
     * schema) ahead of their external driver integration (Tahap 7 in the
     * design doc) but have no real sender yet — enabling them would just
     * crash on first dispatch.
     */
    public function isImplemented(): bool
    {
        return match ($this) {
            self::Database, self::Mail => true,
            self::Push, self::Whatsapp, self::Sms => false,
        };
    }
}
