<?php

namespace Modules\HumanResource\Services;

use App\Models\User;
use App\Support\Http\Exceptions\ConflictException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Modules\Academic\Models\Employee;
use Modules\ApprovalWorkflow\Enums\ApprovalApproverType;
use Modules\ApprovalWorkflow\Models\ApprovalAction;
use Modules\ApprovalWorkflow\Models\ApprovalRequest;
use Modules\ApprovalWorkflow\Models\ApprovalRequestStep;
use Modules\ApprovalWorkflow\Models\ApprovalWorkflow;
use Modules\ApprovalWorkflow\Services\ApprovalActionService;
use Modules\ApprovalWorkflow\Services\ApprovalRequestService;
use Modules\HumanResource\Enums\HrRequestStatus;
use Modules\HumanResource\Enums\HrRequestType;
use Modules\HumanResource\Enums\LeaveStatus;
use Modules\HumanResource\Models\HrRequest;
use Modules\HumanResource\Services\Concerns\SavesRecordWithFiles;
use Modules\UserManagement\Models\Role;

/**
 * Satu pintu seluruh pengajuan SDM di atas Modul ApprovalWorkflow:
 * pengajuan dikirim lewat ApprovalRequestService::submit(), keputusan
 * diambil lewat ApprovalActionService (beserta otorisasi per langkah dari
 * ApprovalRequestStepPolicy), dan hasil akhirnya disalin balik ke
 * hr_requests/leave_requests oleh listener SyncHrRequestDecision.
 */
class HrRequestService
{
    use SavesRecordWithFiles;

    /** Nilai `workflowable_type` untuk alur persetujuan SDM di approval_workflows. */
    public const WORKFLOWABLE_TYPE = 'hr_request';

    /** Field pegawai yang boleh diminta diubah lewat pengajuan perubahan data. */
    public const DATA_CHANGE_FIELDS = ['name', 'email', 'phone', 'address', 'gender', 'birth_place', 'birth_date', 'nik'];

    public function __construct(
        private readonly ApprovalRequestService $approvalRequests,
        private readonly ApprovalActionService $approvalActions,
        private readonly HrNotificationService $notifications,
    ) {}

    /**
     * @param  array<string, mixed>  $data  title, description, payload, attachment_file_id, leave_request_id
     */
    public function submit(Employee $employee, HrRequestType $type, array $data, User $requester): HrRequest
    {
        if (! $employee->is_active) {
            throw new ConflictException('Pegawai nonaktif tidak dapat mengajukan permohonan.');
        }

        if ($type === HrRequestType::DataChange) {
            $this->assertValidDataChange($data);
        }

        $workflow = $this->resolveWorkflow($type);

        return DB::transaction(function () use ($employee, $type, $data, $requester, $workflow): HrRequest {
            $hrRequest = new HrRequest([
                'university_id' => $employee->university_id,
                'employee_id' => $employee->id,
                'type' => $type,
                'status' => HrRequestStatus::Pending,
                'requested_by' => $requester->id,
            ]);

            $this->saveWithFiles($hrRequest, Arr::only($data, [
                'title', 'description', 'payload', 'attachment_file_id', 'leave_request_id',
            ]), ['attachment_file_id'], $requester);

            $approval = $this->approvalRequests->submit($hrRequest, $workflow, $requester, $hrRequest->title);
            $hrRequest->update(['approval_request_id' => $approval->id]);

            $this->notifications->notifyHrAdministrators('hr.request_submitted', [
                'employee' => $employee->name,
                'type' => $type->label(),
                'title' => $hrRequest->title,
            ]);

            return $hrRequest;
        });
    }

    public function approve(HrRequest $hrRequest, User $actor, ?string $note): HrRequest
    {
        $step = $this->actionableStep($hrRequest, $actor);

        DB::transaction(function () use ($hrRequest, $step, $actor, $note): void {
            $this->audit->recordAction($hrRequest, AuditAction::Approved, ['step' => $step->workflowStep->name, 'note' => $note]);
            $this->approvalActions->approve($step, $actor, $note);
        });

        return $hrRequest->refresh();
    }

    public function reject(HrRequest $hrRequest, User $actor, string $note): HrRequest
    {
        $step = $this->actionableStep($hrRequest, $actor);

        DB::transaction(function () use ($hrRequest, $step, $actor, $note): void {
            $this->audit->recordAction($hrRequest, AuditAction::Rejected, ['step' => $step->workflowStep->name, 'note' => $note]);
            $this->approvalActions->reject($step, $actor, $note);
        });

        return $hrRequest->refresh();
    }

    /**
     * Dikembalikan ke pemohon untuk direvisi (catatan wajib). Pemohon lalu
     * merevisi dan mengajukan ulang lewat resubmit().
     */
    public function returnToRequester(HrRequest $hrRequest, User $actor, string $note): HrRequest
    {
        $step = $this->actionableStep($hrRequest, $actor);

        DB::transaction(function () use ($hrRequest, $step, $actor, $note): void {
            $this->audit->recordAction($hrRequest, AuditAction::Returned, ['step' => $step->workflowStep->name, 'note' => $note]);
            $this->approvalActions->returnToRequester($step, $actor, $note);
        });

        return $hrRequest->refresh();
    }

    /**
     * Setujui banyak pengajuan sekaligus (mis. 20 cuti). Setiap pengajuan
     * diproses & diotorisasi sendiri-sendiri — satu yang gagal tidak
     * membatalkan yang lain; hasil per pengajuan dikembalikan ke klien.
     *
     * @param  array<int, string>  $ids
     * @return array<int, array{id: string, ok: bool, message: string}>
     */
    public function bulkApprove(array $ids, User $actor, ?string $note): array
    {
        $results = [];

        foreach (HrRequest::query()->whereKey($ids)->get() as $hrRequest) {
            try {
                if (! $actor->can('approve', $hrRequest)) {
                    throw new ConflictException('Anda tidak berwenang menyetujui jenis pengajuan ini.');
                }

                $this->approve($hrRequest, $actor, $note);
                $results[] = ['id' => $hrRequest->id, 'ok' => true, 'message' => 'Disetujui.'];
            } catch (ConflictException|AuthorizationException $exception) {
                $results[] = ['id' => $hrRequest->id, 'ok' => false, 'message' => $exception->getMessage() ?: 'Tidak dapat diproses.'];
            }
        }

        return $results;
    }

    /**
     * Revisi isi pengajuan yang dikembalikan lalu ajukan ulang. Validasi sama
     * dengan pengajuan baru (pegawai aktif, perubahan data sah; untuk cuti:
     * tidak tumpang tindih & kuota cukup — dicek LeaveService). Alur
     * persetujuan dimulai lagi dari langkah pertama.
     *
     * @param  array<string, mixed>  $data  title, description, payload, attachment_file_id
     */
    public function resubmit(HrRequest $hrRequest, array $data, User $actor): HrRequest
    {
        if ($hrRequest->status !== HrRequestStatus::Returned) {
            throw new ConflictException('Hanya pengajuan yang dikembalikan yang dapat diajukan ulang.');
        }

        $employee = $hrRequest->employee;

        if ($employee === null || ! $employee->is_active) {
            throw new ConflictException('Pegawai nonaktif tidak dapat mengajukan permohonan.');
        }

        if ($hrRequest->type === HrRequestType::DataChange && array_key_exists('payload', $data)) {
            $this->assertValidDataChange($data);
        }

        $approval = $hrRequest->approvalRequest;

        if ($approval === null) {
            throw new ConflictException('Pengajuan ini tidak terhubung ke alur persetujuan.');
        }

        return DB::transaction(function () use ($hrRequest, $data, $actor, $approval): HrRequest {
            $revision = Arr::only($data, ['title', 'description', 'payload', 'attachment_file_id']);

            if ($revision !== []) {
                $this->saveWithFiles($hrRequest, $revision, ['attachment_file_id'], $actor);
            }

            $hrRequest->update(['status' => HrRequestStatus::Pending, 'returned_at' => null]);
            $hrRequest->leaveRequest?->update(['status' => LeaveStatus::Submitted, 'submitted_at' => now()]);

            $this->approvalActions->resubmit($approval, $actor);
            $this->audit->recordAction($hrRequest, AuditAction::Resubmitted, ['resubmission' => $approval->refresh()->resubmission_count]);

            return $hrRequest->refresh();
        });
    }

    /**
     * Dipanggil listener saat ApprovalRequest dikembalikan ke pemohon.
     */
    public function applyReturn(ApprovalRequest $approval, string $note): void
    {
        $hrRequest = HrRequest::query()->where('approval_request_id', $approval->id)->first();

        if ($hrRequest === null || $hrRequest->status !== HrRequestStatus::Pending) {
            return;
        }

        DB::transaction(function () use ($hrRequest, $note): void {
            $hrRequest->update(['status' => HrRequestStatus::Returned, 'returned_at' => now(), 'approval_note' => $note]);
            $hrRequest->leaveRequest?->update(['status' => LeaveStatus::Returned, 'approval_note' => $note]);
        });

        if ($hrRequest->employee !== null) {
            $this->notifications->notifyEmployee($hrRequest->employee, 'hr.request_returned', [
                'title' => $hrRequest->title,
                'note' => $note,
            ]);
        }
    }

    /**
     * Dibatalkan pemohon (atau SDM) selama masih menunggu atau dikembalikan.
     * ApprovalRequest terkait di-soft-delete supaya tidak lagi muncul di
     * kotak persetujuan mana pun.
     */
    public function cancel(HrRequest $hrRequest): HrRequest
    {
        if (! in_array($hrRequest->status, [HrRequestStatus::Pending, HrRequestStatus::Returned], true)) {
            throw new ConflictException('Hanya pengajuan yang masih menunggu atau dikembalikan yang dapat dibatalkan.');
        }

        DB::transaction(function () use ($hrRequest): void {
            $hrRequest->update(['status' => HrRequestStatus::Cancelled, 'cancelled_at' => now()]);
            $hrRequest->leaveRequest?->update(['status' => LeaveStatus::Cancelled, 'cancelled_at' => now()]);
            $hrRequest->approvalRequest?->delete();
        });

        return $hrRequest->refresh();
    }

    public function markProcessed(HrRequest $hrRequest, User $actor): HrRequest
    {
        if ($hrRequest->status !== HrRequestStatus::Approved || ! $hrRequest->requiresProcessing()) {
            throw new ConflictException('Hanya pengajuan mutasi/kenaikan jabatan/dokumen yang sudah disetujui yang dapat ditandai selesai diproses.');
        }

        if ($hrRequest->processed_at === null) {
            $hrRequest->update(['processed_at' => now(), 'processed_by' => $actor->id]);
        }

        return $hrRequest;
    }

    /**
     * Dipanggil listener saat ApprovalRequest selesai (disetujui seluruh
     * langkah / ditolak). Idempotent: pengajuan yang sudah tidak "pending"
     * (mis. sudah dibatalkan) tidak diubah lagi.
     */
    public function applyDecision(ApprovalRequest $approval, bool $approved): void
    {
        $hrRequest = HrRequest::query()->where('approval_request_id', $approval->id)->first();

        if ($hrRequest === null || $hrRequest->status !== HrRequestStatus::Pending) {
            return;
        }

        $lastAction = ApprovalAction::query()
            ->whereIn('approval_request_step_id', $approval->steps()->pluck('id'))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->first();

        $actorId = $lastAction->acted_by ?? null;
        $note = $lastAction->comment ?? null;

        DB::transaction(function () use ($hrRequest, $approved, $actorId, $note): void {
            if ($approved) {
                $hrRequest->update([
                    'status' => HrRequestStatus::Approved,
                    'approved_by' => $actorId,
                    'approved_at' => now(),
                    'approval_note' => $note,
                ]);

                $hrRequest->leaveRequest?->update([
                    'status' => LeaveStatus::Approved,
                    'approved_by' => $actorId,
                    'approved_at' => now(),
                    'approval_note' => $note,
                ]);

                if ($hrRequest->type === HrRequestType::DataChange) {
                    $this->applyDataChange($hrRequest);
                }
            } else {
                $hrRequest->update([
                    'status' => HrRequestStatus::Rejected,
                    'rejected_by' => $actorId,
                    'rejected_at' => now(),
                    'approval_note' => $note,
                ]);

                $hrRequest->leaveRequest?->update([
                    'status' => LeaveStatus::Rejected,
                    'rejected_by' => $actorId,
                    'rejected_at' => now(),
                    'approval_note' => $note,
                ]);
            }
        });

        $employee = $hrRequest->employee;

        if ($employee !== null) {
            $this->notifications->notifyEmployee($employee, 'hr.request_decided', [
                'title' => $hrRequest->title,
                'status' => $approved ? 'disetujui' : 'ditolak',
            ]);
        }
    }

    /**
     * Langkah persetujuan yang sedang berjalan, setelah memastikan actor
     * memang berhak bertindak di langkah itu (ApprovalRequestStepPolicy —
     * sumber kebenaran yang sama dengan halaman Persetujuan umum).
     */
    public function actionableStep(HrRequest $hrRequest, User $actor): ApprovalRequestStep
    {
        if ($hrRequest->status !== HrRequestStatus::Pending) {
            throw new ConflictException('Pengajuan ini sudah diputuskan atau dibatalkan.');
        }

        $step = $hrRequest->approvalRequest?->currentStep;

        if ($step === null) {
            throw new ConflictException('Pengajuan ini tidak memiliki langkah persetujuan yang aktif.');
        }

        Gate::forUser($actor)->authorize('act', $step);

        return $step;
    }

    public function canAct(HrRequest $hrRequest, User $actor): bool
    {
        $step = $hrRequest->approvalRequest?->currentStep;

        return $hrRequest->status === HrRequestStatus::Pending
            && $step !== null
            && Gate::forUser($actor)->allows('act', $step);
    }

    /**
     * Alur yang paling spesifik menang: workflow dengan conditions
     * {"type": "leave"} didahulukan atas workflow tanpa kondisi. Kalau
     * universitas belum mengatur alur sama sekali, dibuatkan alur default
     * satu langkah: diputuskan oleh role Bagian SDM.
     */
    public function resolveWorkflow(HrRequestType $type): ApprovalWorkflow
    {
        $workflow = ApprovalWorkflow::query()
            ->where('workflowable_type', self::WORKFLOWABLE_TYPE)
            ->where('is_active', true)
            ->get()
            ->filter(function (ApprovalWorkflow $workflow) use ($type): bool {
                $conditions = $workflow->conditions ?? [];

                return $conditions === [] || ($conditions['type'] ?? null) === $type->value;
            })
            ->sortByDesc(fn (ApprovalWorkflow $workflow): int => count($workflow->conditions ?? []))
            ->first();

        return ($workflow ?? $this->createDefaultWorkflow())->load('steps');
    }

    private function createDefaultWorkflow(): ApprovalWorkflow
    {
        $role = Role::query()->where('slug', 'hr_administrator')->whereNull('university_id')->first();

        if ($role === null) {
            throw new ConflictException('Role Bagian SDM (hr_administrator) belum tersedia. Jalankan seeder role terlebih dahulu.');
        }

        $workflow = ApprovalWorkflow::query()->firstOrCreate(
            ['workflowable_type' => self::WORKFLOWABLE_TYPE, 'name' => 'Persetujuan SDM'],
            ['description' => 'Alur default pengajuan SDM — diputuskan oleh Bagian SDM.', 'is_active' => true],
        );

        if ($workflow->steps()->doesntExist()) {
            $workflow->steps()->create([
                'sequence' => 1,
                'name' => 'Verifikasi Bagian SDM',
                'approver_type' => ApprovalApproverType::Role,
                'approver_role_id' => $role->id,
            ]);
        }

        return $workflow;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function assertValidDataChange(array $data): void
    {
        $changes = (array) data_get($data, 'payload.changes', []);

        if ($changes === [] || array_diff(array_keys($changes), self::DATA_CHANGE_FIELDS) !== []) {
            throw ValidationException::withMessages(['payload.changes' => 'Isi minimal satu data yang ingin diubah (nama, email, telepon, alamat, jenis kelamin, tempat/tanggal lahir, NIK, NPWP, atau rekening).']);
        }
    }

    private function applyDataChange(HrRequest $hrRequest): void
    {
        $employee = $hrRequest->employee;
        $changes = Arr::only((array) data_get($hrRequest->payload, 'changes', []), self::DATA_CHANGE_FIELDS);

        if ($employee === null || $changes === []) {
            return;
        }

        app(EmployeeService::class)->update($employee, $changes);
    }
}
