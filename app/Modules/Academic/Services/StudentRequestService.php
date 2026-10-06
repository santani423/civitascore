<?php

namespace Modules\Academic\Services;

use App\Models\User;
use App\Support\Http\Exceptions\ConflictException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Modules\Academic\Enums\StudentRequestStatus;
use Modules\Academic\Enums\StudentRequestType;
use Modules\Academic\Enums\StudentStatus;
use Modules\Academic\Models\AcademicTerm;
use Modules\Academic\Models\Student;
use Modules\Academic\Models\StudentRequest;
use Modules\ApprovalWorkflow\Enums\ApprovalApproverType;
use Modules\ApprovalWorkflow\Models\ApprovalAction;
use Modules\ApprovalWorkflow\Models\ApprovalRequest;
use Modules\ApprovalWorkflow\Models\ApprovalRequestStep;
use Modules\ApprovalWorkflow\Models\ApprovalWorkflow;
use Modules\ApprovalWorkflow\Services\ApprovalActionService;
use Modules\ApprovalWorkflow\Services\ApprovalRequestService;
use Modules\FileManagement\Support\FileAttacher;
use Modules\UserManagement\Models\Role;

/**
 * Satu pintu pengajuan akademik mahasiswa (cuti, aktif kembali, perubahan
 * data, surat keterangan, lainnya) di atas Modul ApprovalWorkflow — pola
 * yang sama dengan HrRequestService: pengajuan dikirim lewat
 * ApprovalRequestService::submit(), keputusan diambil lewat
 * ApprovalActionService (beserta otorisasi per langkah dari
 * ApprovalRequestStepPolicy), dan hasil akhirnya disalin balik ke
 * student_requests oleh listener SyncStudentRequestDecision — sehingga
 * pengajuan juga muncul & bisa diputuskan dari halaman Persetujuan umum.
 *
 * Mahasiswa tidak pernah bisa mengubah status persetujuan: create/update/
 * submit/cancel hanya menyentuh pengajuan miliknya yang masih draft atau
 * sedang ditinjau.
 */
class StudentRequestService
{
    /** Nilai `workflowable_type` untuk alur persetujuan pengajuan mahasiswa di approval_workflows. */
    public const WORKFLOWABLE_TYPE = 'student_request';

    /**
     * Data induk yang hanya bisa diubah lewat pengajuan (butuh verifikasi
     * Bagian Akademik). Kontak (telepon/alamat) & foto diubah langsung di
     * Profil tanpa pengajuan.
     */
    public const DATA_CHANGE_FIELDS = ['name', 'email', 'birth_place', 'tanggal_lahir', 'gender'];

    /** Jenis surat keterangan yang bisa diajukan. */
    public const LETTER_TYPES = [
        'active_student' => 'Surat Keterangan Aktif Kuliah',
        'good_conduct' => 'Surat Keterangan Berkelakuan Baik',
        'recommendation' => 'Surat Rekomendasi',
        'other' => 'Surat Keterangan Lainnya',
    ];

    public function __construct(
        private readonly ApprovalRequestService $approvalRequests,
        private readonly ApprovalActionService $approvalActions,
        private readonly StudentNotificationService $notifications,
        private readonly FileAttacher $files,
    ) {}

    /**
     * @param  array<string, mixed>  $data  type, title, description, payload, attachment_file_id, submit
     */
    public function create(Student $student, array $data, User $actor): StudentRequest
    {
        $type = StudentRequestType::from((string) $data['type']);
        $payload = $this->validatePayload($student, $type, (array) ($data['payload'] ?? []));
        $attachment = $this->files->resolve($data['attachment_file_id'] ?? null, 'attachment_file_id', $actor);

        $request = DB::transaction(function () use ($student, $type, $data, $payload, $attachment, $actor): StudentRequest {
            $request = StudentRequest::query()->create([
                'university_id' => $student->university_id,
                'student_id' => $student->id,
                'type' => $type,
                'status' => StudentRequestStatus::Draft,
                'title' => trim((string) $data['title']),
                'description' => $data['description'] ?? null,
                'payload' => $payload,
                'attachment_file_id' => $attachment?->id,
                'academic_term_id' => $payload['academic_term_id'] ?? AcademicTerm::query()->where('is_current', true)->value('id'),
                'requested_by' => $actor->id,
            ]);

            $this->files->attach($attachment, $request);

            return $request;
        });

        return ($data['submit'] ?? false) ? $this->submit($request, $actor) : $request;
    }

    /**
     * @param  array<string, mixed>  $data  title, description, payload, attachment_file_id
     */
    public function update(StudentRequest $request, array $data, User $actor): StudentRequest
    {
        if ($request->status !== StudentRequestStatus::Draft) {
            throw new ConflictException('Pengajuan yang sudah dikirim tidak dapat diubah lagi.');
        }

        $payload = array_key_exists('payload', $data)
            ? $this->validatePayload($request->student, $request->type, (array) $data['payload'])
            : $request->payload;

        $attachment = array_key_exists('attachment_file_id', $data)
            ? $this->files->resolve($data['attachment_file_id'], 'attachment_file_id', $actor, $request)
            : $request->attachment;

        DB::transaction(function () use ($request, $data, $payload, $attachment): void {
            $request->update([
                'title' => array_key_exists('title', $data) ? trim((string) $data['title']) : $request->title,
                'description' => array_key_exists('description', $data) ? $data['description'] : $request->description,
                'payload' => $payload,
                'attachment_file_id' => $attachment?->id,
            ]);

            $this->files->attach($attachment, $request);
        });

        return $request->refresh();
    }

    public function submit(StudentRequest $request, User $actor): StudentRequest
    {
        if ($request->status !== StudentRequestStatus::Draft) {
            throw new ConflictException('Pengajuan ini sudah dikirim sebelumnya.');
        }

        $student = $request->student;

        // Aturan dicek ulang saat dikirim (bukan hanya saat draft dibuat) —
        // status mahasiswa bisa sudah berubah sejak draft disimpan.
        $this->validatePayload($student, $request->type, (array) $request->payload);
        $this->assertNoDuplicateInReview($request);

        $workflow = $this->resolveWorkflow($request->type);

        DB::transaction(function () use ($request, $workflow, $actor): void {
            $request->update(['status' => StudentRequestStatus::Submitted, 'submitted_at' => now()]);

            $approval = $this->approvalRequests->submit($request, $workflow, $actor, $request->title);
            $request->update(['approval_request_id' => $approval->id]);
        });

        $this->notifications->notifyRole('academic_administrator', 'student.request_submitted', [
            'type' => $request->type->label(),
            'student' => $student->name,
            'nim' => $student->nim,
            'title' => $request->title,
        ], '/akademik/pengajuan-mahasiswa');

        return $request->refresh();
    }

    /**
     * Dibatalkan pemohon selama masih draft atau sedang ditinjau.
     * ApprovalRequest terkait di-soft-delete supaya tidak lagi muncul di
     * kotak persetujuan mana pun (sama seperti HrRequestService::cancel()).
     */
    public function cancel(StudentRequest $request): StudentRequest
    {
        if (! in_array($request->status, [StudentRequestStatus::Draft, StudentRequestStatus::Submitted], true)) {
            throw new ConflictException('Hanya pengajuan yang masih draft atau sedang ditinjau yang dapat dibatalkan.');
        }

        DB::transaction(function () use ($request): void {
            $request->update(['status' => StudentRequestStatus::Cancelled, 'cancelled_at' => now()]);
            $request->approvalRequest?->delete();
        });

        return $request->refresh();
    }

    public function approve(StudentRequest $request, User $actor, ?string $note): StudentRequest
    {
        $step = $this->actionableStep($request, $actor);
        $this->approvalActions->approve($step, $actor, $note);

        return $request->refresh();
    }

    public function reject(StudentRequest $request, User $actor, string $note): StudentRequest
    {
        $step = $this->actionableStep($request, $actor);
        $this->approvalActions->reject($step, $actor, $note);

        return $request->refresh();
    }

    public function canAct(StudentRequest $request, User $actor): bool
    {
        $step = $request->approvalRequest?->currentStep;

        return $request->status === StudentRequestStatus::Submitted
            && $step !== null
            && Gate::forUser($actor)->allows('act', $step);
    }

    /**
     * Dipanggil listener saat ApprovalRequest selesai (disetujui seluruh
     * langkah / ditolak). Idempotent: pengajuan yang sudah tidak "submitted"
     * (mis. sudah dibatalkan) tidak diubah lagi.
     */
    public function applyDecision(ApprovalRequest $approval, bool $approved): void
    {
        $request = StudentRequest::query()->where('approval_request_id', $approval->id)->first();

        if ($request === null || $request->status !== StudentRequestStatus::Submitted) {
            return;
        }

        $lastAction = ApprovalAction::query()
            ->whereIn('approval_request_step_id', $approval->steps()->pluck('id'))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->first();

        DB::transaction(function () use ($request, $approved, $lastAction): void {
            $request->update([
                'status' => $approved ? StudentRequestStatus::Approved : StudentRequestStatus::Rejected,
                'decided_by' => $lastAction->acted_by ?? null,
                'decided_at' => now(),
                'decision_note' => $lastAction->comment ?? null,
            ]);

            if ($approved) {
                $this->applyEffect($request);
            }
        });

        $this->notifications->notifyStudent($request->student, 'student.request_decided', [
            'title' => $request->title,
            'status' => $approved ? 'disetujui' : 'ditolak',
        ], '/portal/pengajuan');
    }

    /**
     * Langkah persetujuan yang sedang berjalan, setelah memastikan actor
     * memang berhak bertindak di langkah itu (ApprovalRequestStepPolicy).
     */
    public function actionableStep(StudentRequest $request, User $actor): ApprovalRequestStep
    {
        if ($request->status !== StudentRequestStatus::Submitted) {
            throw new ConflictException('Pengajuan ini sudah diputuskan atau dibatalkan.');
        }

        $step = $request->approvalRequest?->currentStep;

        if ($step === null) {
            throw new ConflictException('Pengajuan ini tidak memiliki langkah persetujuan yang aktif.');
        }

        Gate::forUser($actor)->authorize('act', $step);

        return $step;
    }

    /**
     * Alur yang paling spesifik menang: workflow dengan conditions
     * {"type": "leave"} didahulukan atas workflow tanpa kondisi. Kalau
     * universitas belum mengatur alur sama sekali, dibuatkan alur default
     * satu langkah: diputuskan oleh role Bagian Akademik.
     */
    public function resolveWorkflow(StudentRequestType $type): ApprovalWorkflow
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

    /**
     * Validasi isi pengajuan per jenis — dipanggil saat draft dibuat/diubah
     * DAN saat dikirim.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function validatePayload(Student $student, StudentRequestType $type, array $payload): array
    {
        return match ($type) {
            StudentRequestType::Leave => $this->validateLeave($student, $payload),
            StudentRequestType::Reactivation => $this->validateReactivation($student),
            StudentRequestType::DataChange => $this->validateDataChange($student, $payload),
            StudentRequestType::Letter => $this->validateLetter($student, $payload),
            StudentRequestType::Other => [],
        };
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function validateLeave(Student $student, array $payload): array
    {
        if ($student->status !== StudentStatus::Active) {
            throw new ConflictException('Cuti akademik hanya dapat diajukan oleh mahasiswa berstatus Aktif.');
        }

        $termId = $payload['academic_term_id'] ?? AcademicTerm::query()->where('is_current', true)->value('id');

        if ($termId === null || ! AcademicTerm::query()->whereKey($termId)->exists()) {
            throw ValidationException::withMessages(['payload.academic_term_id' => 'Semester cuti tidak ditemukan.']);
        }

        return ['academic_term_id' => (string) $termId];
    }

    /**
     * @return array<string, mixed>
     */
    private function validateReactivation(Student $student): array
    {
        if (! in_array($student->status, [StudentStatus::Leave, StudentStatus::Inactive], true)) {
            throw new ConflictException('Pengajuan aktif kembali hanya untuk mahasiswa yang sedang Cuti atau Nonaktif.');
        }

        return [];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function validateDataChange(Student $student, array $payload): array
    {
        $changes = (array) ($payload['changes'] ?? []);

        if ($changes === [] || array_diff(array_keys($changes), self::DATA_CHANGE_FIELDS) !== []) {
            throw ValidationException::withMessages([
                'payload.changes' => 'Isi minimal satu data yang ingin diubah (nama, email, tempat lahir, tanggal lahir, atau jenis kelamin).',
            ]);
        }

        $validator = Validator::make($changes, [
            'name' => ['sometimes', 'string', 'min:3', 'max:255'],
            'email' => [
                'sometimes', 'email', 'max:255',
                Rule::unique('users', 'email')->ignore($student->user_id),
            ],
            'birth_place' => ['sometimes', 'string', 'max:100'],
            'tanggal_lahir' => ['sometimes', 'date', 'before:today'],
            'gender' => ['sometimes', Rule::in(['male', 'female'])],
        ], [
            'email.unique' => 'Email tersebut sudah digunakan akun lain.',
        ]);

        if ($validator->fails()) {
            throw ValidationException::withMessages(collect($validator->errors()->messages())
                ->mapWithKeys(fn (array $messages, string $field) => ["payload.changes.{$field}" => $messages])
                ->all());
        }

        return ['changes' => Arr::only($changes, self::DATA_CHANGE_FIELDS)];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function validateLetter(Student $student, array $payload): array
    {
        $validator = Validator::make($payload, [
            'letter_type' => ['required', Rule::in(array_keys(self::LETTER_TYPES))],
            'purpose' => ['required', 'string', 'max:255'],
        ], [
            'letter_type.required' => 'Pilih jenis surat.',
            'purpose.required' => 'Tuliskan keperluan surat.',
        ]);

        if ($validator->fails()) {
            throw ValidationException::withMessages(collect($validator->errors()->messages())
                ->mapWithKeys(fn (array $messages, string $field) => ["payload.{$field}" => $messages])
                ->all());
        }

        if ($payload['letter_type'] === 'active_student' && $student->status !== StudentStatus::Active) {
            throw new ConflictException('Surat keterangan aktif kuliah hanya untuk mahasiswa berstatus Aktif.');
        }

        return Arr::only($payload, ['letter_type', 'purpose']);
    }

    private function assertNoDuplicateInReview(StudentRequest $request): void
    {
        $duplicate = StudentRequest::query()
            ->where('student_id', $request->student_id)
            ->where('type', $request->type)
            ->where('status', StudentRequestStatus::Submitted)
            ->whereKeyNot($request->id)
            ->exists();

        if ($duplicate) {
            throw new ConflictException("Masih ada pengajuan {$request->type->label()} yang sedang ditinjau.");
        }
    }

    /**
     * Efek pengajuan yang disetujui ke data induk mahasiswa. Perubahan
     * status ikut tercatat di riwayat status lewat StudentObserver.
     */
    private function applyEffect(StudentRequest $request): void
    {
        $student = $request->student;

        match ($request->type) {
            StudentRequestType::Leave => $this->changeStatus($student, StudentStatus::Leave, 'Cuti akademik disetujui'),
            StudentRequestType::Reactivation => $this->changeStatus($student, StudentStatus::Active, 'Aktif kembali disetujui'),
            StudentRequestType::DataChange => $this->applyDataChange($student, (array) data_get($request->payload, 'changes', [])),
            StudentRequestType::Letter, StudentRequestType::Other => null,
        };
    }

    private function changeStatus(Student $student, StudentStatus $status, string $reason): void
    {
        $student->statusChangeReason = $reason;
        $student->update(['status' => $status]);
    }

    /**
     * @param  array<string, mixed>  $changes
     */
    private function applyDataChange(Student $student, array $changes): void
    {
        $changes = Arr::only($changes, self::DATA_CHANGE_FIELDS);

        if ($changes === []) {
            return;
        }

        $student->update($changes);

        // Email & nama juga identitas login — dijaga sinkron. Email baru
        // dicek ulang keunikannya saat diterapkan (bisa sudah dipakai akun
        // lain sejak pengajuan dikirim); kalau bentrok, email login tidak
        // diubah supaya tidak memutus akses mahasiswa.
        $user = $student->user;

        if ($user === null) {
            return;
        }

        $userChanges = [];

        if (isset($changes['name'])) {
            $userChanges['name'] = $changes['name'];
        }

        if (isset($changes['email']) && ! User::query()->where('email', $changes['email'])->whereKeyNot($user->id)->exists()) {
            $userChanges['email'] = $changes['email'];
        }

        if ($userChanges !== []) {
            $user->update($userChanges);
        }
    }

    private function createDefaultWorkflow(): ApprovalWorkflow
    {
        $role = Role::query()->where('slug', 'academic_administrator')->whereNull('university_id')->first();

        if ($role === null) {
            throw new ConflictException('Role Bagian Akademik (academic_administrator) belum tersedia. Jalankan seeder role terlebih dahulu.');
        }

        $workflow = ApprovalWorkflow::query()->firstOrCreate(
            ['workflowable_type' => self::WORKFLOWABLE_TYPE, 'name' => 'Persetujuan Pengajuan Mahasiswa'],
            ['description' => 'Alur default pengajuan akademik mahasiswa — diputuskan oleh Bagian Akademik.', 'is_active' => true],
        );

        if ($workflow->steps()->doesntExist()) {
            $workflow->steps()->create([
                'sequence' => 1,
                'name' => 'Verifikasi Bagian Akademik',
                'approver_type' => ApprovalApproverType::Role,
                'approver_role_id' => $role->id,
            ]);
        }

        return $workflow;
    }
}
