<?php

namespace Modules\Admission\Listeners;

use App\Support\Tenancy\TenantContext;
use Illuminate\Events\Dispatcher;
use Modules\Admission\Enums\ChangeRequestStatus;
use Modules\Admission\Enums\ChangeRequestType;
use Modules\Admission\Models\ChangeRequest;
use Modules\Admission\Models\Refund;
use Modules\Admission\Models\SelectionRun;
use Modules\Admission\Services\Announcement\DecisionService;
use Modules\Admission\Services\Announcement\PublicationService;
use Modules\Admission\Services\Payment\RefundService;
use Modules\Admission\Services\ReRegistration\ReRegistrationService;
use Modules\Admission\Services\Selection\ScoreService;
use Modules\Admission\Services\Selection\SelectionRunService;
use Modules\ApprovalWorkflow\Events\ApprovalRequestApproved;
use Modules\ApprovalWorkflow\Events\ApprovalRequestRejected;
use Modules\ApprovalWorkflow\Events\ApprovalRequestReturned;
use Modules\ApprovalWorkflow\Models\ApprovalRequest;

/**
 * Menyalin keputusan akhir approval engine ke objek PMB (KD-05). Sinkron —
 * berjalan di transaksi aksi approve/reject, seperti SyncStudentRequestDecision.
 * (Catatan: 11 §A.11 menyebut listener `ApprovalCompleted`; event yang ada di
 * kode adalah ApprovalRequestApproved/Rejected/Returned.)
 */
class SyncApprovalDecisions
{
    public function handleApproved(ApprovalRequestApproved $event): void
    {
        $this->dispatch($event->request, 'approved');
    }

    public function handleRejected(ApprovalRequestRejected $event): void
    {
        $this->dispatch($event->request, 'rejected');
    }

    public function handleReturned(ApprovalRequestReturned $event): void
    {
        $this->dispatch($event->request, 'returned');
    }

    private function dispatch(ApprovalRequest $request, string $outcome): void
    {
        $tenant = app(TenantContext::class);
        $previous = $tenant->universityId();

        if ($request->university_id !== null) {
            $tenant->setUniversityId((string) $request->university_id);
        }

        try {
            match ($request->requestable_type) {
                (new SelectionRun)->getMorphClass() => $this->run($request, $outcome),
                (new Refund)->getMorphClass() => $this->refund($request, $outcome),
                (new ChangeRequest)->getMorphClass() => $this->change($request, $outcome),
                default => null,
            };
        } finally {
            $tenant->setUniversityId($previous);
        }
    }

    private function run(ApprovalRequest $request, string $outcome): void
    {
        $run = SelectionRun::query()->find($request->requestable_id);

        if ($run === null) {
            return;
        }

        $outcome === 'approved' ? app(SelectionRunService::class)->onApproved($run) : app(SelectionRunService::class)->onRejected($run, $outcome === 'returned');
    }

    private function refund(ApprovalRequest $request, string $outcome): void
    {
        $refund = Refund::query()->find($request->requestable_id);

        if ($refund !== null) {
            $outcome === 'approved' ? app(RefundService::class)->onApproved($refund) : app(RefundService::class)->onRejected($refund);
        }
    }

    private function change(ApprovalRequest $request, string $outcome): void
    {
        $change = ChangeRequest::query()->find($request->requestable_id);

        if ($change === null || $change->status !== ChangeRequestStatus::PendingApproval) {
            return;
        }

        if ($outcome !== 'approved') {
            $change->forceFill(['status' => $outcome === 'returned' ? ChangeRequestStatus::Returned : ChangeRequestStatus::Rejected, 'decided_at' => now()])->save();

            return;
        }

        $change->forceFill(['status' => ChangeRequestStatus::Approved, 'decided_at' => now()])->save();

        match ($change->type) {
            ChangeRequestType::ScoreCorrection => app(ScoreService::class)->applyLockedCorrection($change),
            ChangeRequestType::ResultCorrection => app(DecisionService::class)->applyCorrection($change),
            ChangeRequestType::ReRegistrationCancellation => app(DecisionService::class)->applyCancellation($change),
            ChangeRequestType::BulkExtension => app(ReRegistrationService::class)->applyBulkExtension($change),
            ChangeRequestType::PublicationAcceleration => app(PublicationService::class)->applyAcceleration($change),
            ChangeRequestType::BulkRefund => app(RefundService::class)->applyBulk($change),
            ChangeRequestType::Refund => null,
        };
    }

    public function subscribe(Dispatcher $events): void
    {
        $events->listen(ApprovalRequestApproved::class, [self::class, 'handleApproved']);
        $events->listen(ApprovalRequestRejected::class, [self::class, 'handleRejected']);
        $events->listen(ApprovalRequestReturned::class, [self::class, 'handleReturned']);
    }
}
