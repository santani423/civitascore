<?php

namespace Modules\Finance\Services;

use App\Models\User;
use App\Support\Http\Exceptions\ConflictException;
use Illuminate\Support\Facades\DB;
use Modules\Academic\Models\Student;
use Modules\AuditLog\Enums\AuditAction;
use Modules\AuditLog\Services\AuditLogService;
use Modules\Finance\Enums\UktRequestStatus;
use Modules\Finance\Models\StudentUktAssignment;
use Modules\Finance\Models\UktChangeRequest;
use Modules\Finance\Models\UktGroup;
use Modules\Finance\Support\Approvals;

/**
 * Penetapan & validasi golongan UKT (F-04, BR-KEU-16..19). Satu penetapan
 * aktif per mahasiswa; penetapan baru menggantikan yang lama (riwayat
 * tetap). Perubahan memengaruhi tagihan berikutnya — tagihan terbit tidak
 * diubah otomatis (re-bill lewat penyesuaian).
 */
class UktService
{
    public function __construct(
        private readonly FinanceNotifier $notifier,
        private readonly AuditLogService $audit,
    ) {}

    public function assign(Student $student, UktGroup $group, ?string $effectiveTermId, string $basis, ?string $decree, User $actor): StudentUktAssignment
    {
        if (! $group->is_active) {
            throw new ConflictException('Golongan UKT ini tidak aktif.');
        }

        return DB::transaction(function () use ($student, $group, $effectiveTermId, $basis, $decree, $actor): StudentUktAssignment {
            StudentUktAssignment::query()->where('student_id', $student->id)->where('status', 'active')->lockForUpdate()
                ->update(['status' => 'superseded', 'superseded_at' => now()]);

            $assignment = StudentUktAssignment::query()->create([
                'student_id' => $student->id,
                'ukt_group_id' => $group->id,
                'effective_from_term_id' => $effectiveTermId,
                'basis' => $basis,
                'decree_number' => $decree,
                'status' => 'active',
                'assigned_by' => $actor->id,
            ]);

            $this->audit->recordAction($assignment, AuditAction::Created, ['student' => $student->nim, 'group' => $group->code], $basis);

            return $assignment;
        });
    }

    public function requestChange(Student $student, ?UktGroup $requested, string $reason, ?string $evidenceFileId, User $actor): UktChangeRequest
    {
        if (UktChangeRequest::query()->where('student_id', $student->id)->where('status', UktRequestStatus::Submitted)->exists()) {
            throw new ConflictException('Masih ada pengajuan perubahan UKT yang sedang diproses.');
        }

        $current = StudentUktAssignment::query()->where('student_id', $student->id)->where('status', 'active')->value('ukt_group_id');

        return UktChangeRequest::query()->create([
            'student_id' => $student->id,
            'current_group_id' => $current,
            'requested_group_id' => $requested?->id,
            'reason' => $reason,
            'evidence_file_id' => $evidenceFileId,
            'status' => UktRequestStatus::Submitted,
            'requested_by' => $actor->id,
        ]);
    }

    public function decide(UktChangeRequest $request, bool $approve, ?UktGroup $group, ?string $effectiveTermId, ?string $note, User $actor): UktChangeRequest
    {
        if ($request->status !== UktRequestStatus::Submitted) {
            throw new ConflictException('Pengajuan ini sudah diputuskan.');
        }

        Approvals::assertDistinct($actor, [$request->requested_by]);

        if ($approve && $group === null) {
            throw new ConflictException('Pilih golongan UKT hasil peninjauan.');
        }

        DB::transaction(function () use ($request, $approve, $group, $effectiveTermId, $note, $actor): void {
            $assignment = $approve
                ? $this->assign($request->student, $group, $effectiveTermId, 'Peninjauan ulang UKT: '.($note ?: $request->reason), null, $actor)
                : null;

            $request->forceFill([
                'status' => $approve ? UktRequestStatus::Approved : UktRequestStatus::Rejected,
                'decided_group_id' => $group?->id,
                'decided_by' => $actor->id,
                'decided_at' => now(),
                'decision_note' => $note,
                'effective_term_id' => $effectiveTermId,
                'assignment_id' => $assignment?->id,
            ])->save();
        });

        $this->notifier->student($request->student, 'keu.ukt_decided', [
            'status' => $approve ? 'disetujui (golongan '.$group->name.')' : 'ditolak',
            'note' => (string) $note,
        ]);

        return $request->fresh();
    }
}
