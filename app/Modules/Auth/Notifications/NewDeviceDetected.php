<?php

namespace Modules\Auth\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Auth\Models\UserDevice;
use Modules\Notification\Notifications\Concerns\HasUlidId;

class NewDeviceDetected extends Notification implements ShouldQueue
{
    use HasUlidId, Queueable;

    public function __construct(private readonly UserDevice $device)
    {
        $this->assignUlidId();
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Login dari perangkat baru terdeteksi')
            ->line("Kami mendeteksi login dari perangkat baru: {$this->device->device_name}.")
            ->line("Platform: {$this->device->platform}")
            ->line('Jika ini bukan Anda, segera amankan akun Anda dan cabut sesi yang tidak dikenal.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'device_id' => $this->device->id,
            'device_name' => $this->device->device_name,
            'platform' => $this->device->platform,
        ];
    }
}
