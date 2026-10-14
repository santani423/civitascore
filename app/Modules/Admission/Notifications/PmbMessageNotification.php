<?php

namespace Modules\Admission\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Admission\Channels\PmbSmsChannel;

/**
 * Notifikasi PMB yang isinya SUDAH dirender (dalam konteks tenant, oleh
 * PmbNotifier) — tidak ikut antrean sendiri; keandalan pengiriman dijamin
 * outbox (13 §A.4), sehingga aman di lingkungan tanpa worker antrean.
 */
class PmbMessageNotification extends Notification
{
    use Queueable;

    /**
     * @param  array<int, string>  $channels  mail | database | sms
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        public readonly string $eventKey,
        public readonly string $subject,
        public readonly string $body,
        public readonly array $channels,
        public readonly array $data = [],
        public readonly ?string $smsBody = null,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return array_values(array_map(
            fn (string $channel): string => $channel === 'sms' ? PmbSmsChannel::class : $channel,
            $this->channels,
        ));
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)->subject($this->subject);

        foreach (preg_split('/\R{2,}/', $this->body) ?: [$this->body] as $paragraph) {
            $message->line($paragraph);
        }

        if (! empty($this->data['link'])) {
            $message->action('Buka Portal', (string) $this->data['link']);
        }

        return $message;
    }

    /** @return array<string, mixed> */
    public function toDatabase(object $notifiable): array
    {
        return [...$this->data, 'event_key' => $this->eventKey, 'title' => $this->subject, 'message' => $this->body];
    }

    public function toSms(object $notifiable): string
    {
        return mb_substr($this->smsBody ?? $this->body, 0, 160);
    }
}
