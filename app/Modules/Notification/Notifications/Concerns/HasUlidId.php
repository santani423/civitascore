<?php

namespace Modules\Notification\Notifications\Concerns;

use Illuminate\Support\Str;

/**
 * Laravel's NotificationSender only fills $this->id with a UUID when it is
 * still empty (Illuminate\Notifications\NotificationSender). Since the
 * `notifications` table PK is a 26-char ULID, every custom Notification
 * class must call assignUlidId() as the first line of its constructor —
 * a trait constructor can't be used here since it would collide with each
 * notification's own parameterized constructor.
 */
trait HasUlidId
{
    protected function assignUlidId(): void
    {
        $this->id = (string) Str::ulid();
    }
}
