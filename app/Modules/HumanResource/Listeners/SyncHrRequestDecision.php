<?php

namespace Modules\HumanResource\Listeners;

use Illuminate\Events\Dispatcher;
use Modules\ApprovalWorkflow\Events\ApprovalRequestApproved;
use Modules\ApprovalWorkflow\Events\ApprovalRequestRejected;
use Modules\HumanResource\Models\HrRequest;
use Modules\HumanResource\Services\HrRequestService;

/**
 * Menyalin keputusan akhir ApprovalWorkflow ke hr_requests/leave_requests.
 * Sengaja sinkron (bukan ShouldQueue): berjalan di transaksi yang sama
 * dengan aksi approve/reject, jadi status SDM tidak pernah tertinggal dari
 * status persetujuannya — dari halaman SDM maupun halaman Persetujuan umum.
 */
class SyncHrRequestDecision
{
    public function __construct(private readonly HrRequestService $requests) {}

    public function handleApproved(ApprovalRequestApproved $event): void
    {
        if ($event->request->requestable_type === (new HrRequest)->getMorphClass()) {
            $this->requests->applyDecision($event->request, approved: true);
        }
    }

    public function handleRejected(ApprovalRequestRejected $event): void
    {
        if ($event->request->requestable_type === (new HrRequest)->getMorphClass()) {
            $this->requests->applyDecision($event->request, approved: false);
        }
    }

    public function subscribe(Dispatcher $events): void
    {
        $events->listen(ApprovalRequestApproved::class, [self::class, 'handleApproved']);
        $events->listen(ApprovalRequestRejected::class, [self::class, 'handleRejected']);
    }
}
