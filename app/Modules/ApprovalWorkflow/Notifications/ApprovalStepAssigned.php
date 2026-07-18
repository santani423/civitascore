<?php

namespace Modules\ApprovalWorkflow\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\ApprovalWorkflow\Models\ApprovalRequestStep;
use Modules\Notification\Notifications\Concerns\HasUlidId;

class ApprovalStepAssigned extends Notification implements ShouldQueue
{
    use HasUlidId, Queueable;

    public function __construct(private readonly ApprovalRequestStep $step)
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
            ->subject('Anda memiliki persetujuan baru menunggu')
            ->line("Langkah '{$this->step->workflowStep->name}' menunggu tindakan Anda.");
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'approval_request_step_id' => $this->step->id,
            'approval_request_id' => $this->step->approval_request_id,
        ];
    }
}
