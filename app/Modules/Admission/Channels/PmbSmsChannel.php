<?php

namespace Modules\Admission\Channels;

use Illuminate\Notifications\Notification;
use Modules\Admission\Contracts\SmsGateway;
use Modules\Admission\Notifications\PmbMessageNotification;

class PmbSmsChannel
{
    public function __construct(private readonly SmsGateway $gateway) {}

    public function send(object $notifiable, Notification $notification): void
    {
        $phone = $notifiable->phone ?? null;

        if (! $phone || ! $notification instanceof PmbMessageNotification) {
            return;
        }

        $this->gateway->send($phone, $notification->toSms($notifiable));
    }
}
