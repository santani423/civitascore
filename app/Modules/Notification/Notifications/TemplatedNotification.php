<?php

namespace Modules\Notification\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Notification\Enums\NotificationChannel;
use Modules\Notification\Notifications\Concerns\HasUlidId;
use Modules\Notification\Services\TemplateRenderer;

/**
 * Generic, template-backed notification any module can send without
 * writing its own Notification class — resolves content from
 * notification_templates by event_key + channel at send time.
 */
class TemplatedNotification extends Notification implements ShouldQueue
{
    use HasUlidId, Queueable;

    /**
     * @param  array<int, NotificationChannel>  $channels
     * @param  array<string, string>  $placeholders
     */
    public function __construct(
        private readonly string $eventKey,
        private readonly array $channels,
        private readonly array $placeholders = [],
    ) {
        $this->assignUlidId();
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return array_map(fn (NotificationChannel $channel): string => $channel->value, $this->channels);
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'event_key' => $this->eventKey,
            'message' => app(TemplateRenderer::class)->render($this->eventKey, NotificationChannel::Database, $this->placeholders),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $body = app(TemplateRenderer::class)->render($this->eventKey, NotificationChannel::Mail, $this->placeholders) ?? '';

        return (new MailMessage)->subject($this->eventKey)->line($body);
    }
}
