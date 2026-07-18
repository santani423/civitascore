<?php

namespace Modules\ApprovalWorkflow\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\ApprovalWorkflow\Models\ApprovalRequest;
use Modules\Notification\Notifications\Concerns\HasUlidId;

class ApprovalRequestDecided extends Notification implements ShouldQueue
{
    use HasUlidId, Queueable;

    public function __construct(private readonly ApprovalRequest $request)
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
            ->subject('Status pengajuan Anda telah diperbarui')
            ->line("Pengajuan Anda kini berstatus: {$this->request->status->value}.");
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'approval_request_id' => $this->request->id,
            'status' => $this->request->status->value,
        ];
    }
}
