<?php

namespace Modules\Academic\Listeners;

use Illuminate\Events\Dispatcher;
use Modules\Academic\Models\StudentRequest;
use Modules\Academic\Services\StudentRequestService;
use Modules\ApprovalWorkflow\Events\ApprovalRequestApproved;
use Modules\ApprovalWorkflow\Events\ApprovalRequestRejected;

/**
 * Menyalin keputusan akhir ApprovalWorkflow ke student_requests (dan
 * menerapkan efeknya: status cuti/aktif kembali, perubahan data). Sengaja
 * sinkron (bukan ShouldQueue) — berjalan di transaksi yang sama dengan aksi
 * approve/reject, jadi status pengajuan mahasiswa tidak pernah tertinggal
 * dari status persetujuannya, dari halaman mana pun keputusan diambil.
 */
class SyncStudentRequestDecision
{
    public function __construct(private readonly StudentRequestService $requests) {}

    public function handleApproved(ApprovalRequestApproved $event): void
    {
        if ($event->request->requestable_type === (new StudentRequest)->getMorphClass()) {
            $this->requests->applyDecision($event->request, approved: true);
        }
    }

    public function handleRejected(ApprovalRequestRejected $event): void
    {
        if ($event->request->requestable_type === (new StudentRequest)->getMorphClass()) {
            $this->requests->applyDecision($event->request, approved: false);
        }
    }

    public function subscribe(Dispatcher $events): void
    {
        $events->listen(ApprovalRequestApproved::class, [self::class, 'handleApproved']);
        $events->listen(ApprovalRequestRejected::class, [self::class, 'handleRejected']);
    }
}
