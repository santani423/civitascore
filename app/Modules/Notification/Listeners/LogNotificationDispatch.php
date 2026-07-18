<?php

namespace Modules\Notification\Listeners;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Events\Dispatcher;
use Illuminate\Notifications\Events\NotificationFailed;
use Illuminate\Notifications\Events\NotificationSending;
use Illuminate\Notifications\Events\NotificationSent;
use Modules\Notification\Enums\NotificationChannel;
use Modules\Notification\Enums\NotificationLogStatus;
use Modules\Notification\Models\NotificationChannelConfig;
use Modules\Notification\Models\NotificationLog;
use Modules\Notification\Models\UserNotificationPreference;

/**
 * Subscribes to Laravel's own notification lifecycle events so every
 * dispatch is logged and gated by channel/user preference — without every
 * Notification class in the app having to duplicate that logic.
 */
class LogNotificationDispatch
{
    public function handleSending(NotificationSending $event): bool
    {
        $channel = NotificationChannel::tryFrom($event->channel);

        if ($channel === null) {
            return true;
        }

        if (! $this->channelEnabled($channel) || ! $this->userOptedIn($event->notifiable, $channel, $event->notification)) {
            $this->log($event->notifiable, $event->notification, $channel, NotificationLogStatus::Skipped);

            return false;
        }

        return true;
    }

    public function handleSent(NotificationSent $event): void
    {
        $channel = NotificationChannel::tryFrom($event->channel);

        if ($channel !== null) {
            $this->log($event->notifiable, $event->notification, $channel, NotificationLogStatus::Sent, sentAt: now());
        }
    }

    public function handleFailed(NotificationFailed $event): void
    {
        $channel = NotificationChannel::tryFrom($event->channel);

        if ($channel !== null) {
            $this->log(
                $event->notifiable,
                $event->notification,
                $channel,
                NotificationLogStatus::Failed,
                errorMessage: ($event->data['exception'] ?? null)?->getMessage() ?? 'Unknown notification failure',
            );
        }
    }

    public function subscribe(Dispatcher $events): void
    {
        $events->listen(NotificationSending::class, [self::class, 'handleSending']);
        $events->listen(NotificationSent::class, [self::class, 'handleSent']);
        $events->listen(NotificationFailed::class, [self::class, 'handleFailed']);
    }

    private function channelEnabled(NotificationChannel $channel): bool
    {
        $isEnabled = NotificationChannelConfig::query()->where('code', $channel->value)->value('is_enabled');

        return $isEnabled === null ? true : (bool) $isEnabled;
    }

    private function userOptedIn(object $notifiable, NotificationChannel $channel, object $notification): bool
    {
        if (! $notifiable instanceof User) {
            return true;
        }

        $specific = UserNotificationPreference::query()
            ->where('user_id', $notifiable->id)
            ->where('channel', $channel->value)
            ->where('notification_type', $notification::class)
            ->first();

        if ($specific) {
            return $specific->is_enabled;
        }

        $wildcard = UserNotificationPreference::query()
            ->where('user_id', $notifiable->id)
            ->where('channel', $channel->value)
            ->where('notification_type', '*')
            ->first();

        return $wildcard === null ? true : $wildcard->is_enabled;
    }

    private function log(
        object $notifiable,
        object $notification,
        NotificationChannel $channel,
        NotificationLogStatus $status,
        ?\DateTimeInterface $sentAt = null,
        ?string $errorMessage = null,
    ): void {
        if (! $notifiable instanceof Model) {
            return;
        }

        NotificationLog::create([
            'notification_id' => $notification->id ?? null,
            'notifiable_type' => $notifiable->getMorphClass(),
            'notifiable_id' => $notifiable->getKey(),
            'channel' => $channel,
            'event_key' => $notification::class,
            'status' => $status,
            'error_message' => $errorMessage,
            'sent_at' => $sentAt,
        ]);
    }
}
