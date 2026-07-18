<?php

namespace Modules\ApprovalWorkflow\Listeners;

use Illuminate\Events\Dispatcher;
use Modules\ApprovalWorkflow\Events\ApprovalRequestApproved;
use Modules\ApprovalWorkflow\Events\ApprovalRequestRejected;
use Modules\ApprovalWorkflow\Events\ApprovalRequestStepAdvanced;
use Modules\ApprovalWorkflow\Events\ApprovalRequestSubmitted;
use Modules\ApprovalWorkflow\Models\ApprovalRequestStep;
use Modules\ApprovalWorkflow\Notifications\ApprovalRequestDecided;
use Modules\ApprovalWorkflow\Notifications\ApprovalStepAssigned;

class SendApprovalNotification
{
    public function handleSubmitted(ApprovalRequestSubmitted $event): void
    {
        $this->notifyAssignedApprover($event->request->currentStep);
    }

    public function handleStepAdvanced(ApprovalRequestStepAdvanced $event): void
    {
        $this->notifyAssignedApprover($event->step);
    }

    public function handleApproved(ApprovalRequestApproved $event): void
    {
        $event->request->requestedBy->notify(new ApprovalRequestDecided($event->request));
    }

    public function handleRejected(ApprovalRequestRejected $event): void
    {
        $event->request->requestedBy->notify(new ApprovalRequestDecided($event->request));
    }

    public function subscribe(Dispatcher $events): void
    {
        $events->listen(ApprovalRequestSubmitted::class, [self::class, 'handleSubmitted']);
        $events->listen(ApprovalRequestStepAdvanced::class, [self::class, 'handleStepAdvanced']);
        $events->listen(ApprovalRequestApproved::class, [self::class, 'handleApproved']);
        $events->listen(ApprovalRequestRejected::class, [self::class, 'handleRejected']);
    }

    /**
     * Role-type steps have no single pre-assigned approver (any user
     * holding the role may act — see ApproverResolver), so there is no
     * recipient to notify automatically for those in Phase 1.
     */
    private function notifyAssignedApprover(?ApprovalRequestStep $step): void
    {
        $step?->assignedApprover?->notify(new ApprovalStepAssigned($step));
    }
}
