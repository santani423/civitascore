<?php

namespace Modules\Admission\Services\Verification;

use App\Models\User;
use App\Support\Http\Exceptions\ConflictException;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Admission\Enums\AccountStatus;
use Modules\Admission\Enums\AssignmentStatus;
use Modules\Admission\Enums\DocumentStage;
use Modules\Admission\Enums\DocumentStatus;
use Modules\Admission\Enums\DuplicateFlagStatus;
use Modules\Admission\Enums\DuplicateFlagType;
use Modules\Admission\Enums\FormStatus;
use Modules\Admission\Enums\Lifecycle;
use Modules\Admission\Enums\RegistrationPaymentStatus;
use Modules\Admission\Enums\SelectionStatus;
use Modules\Admission\Enums\VerificationStatus;
use Modules\Admission\Exceptions\DomainRuleException;
use Modules\Admission\Exceptions\InvalidTransitionException;
use Modules\Admission\Models\ApplicantAccount;
use Modules\Admission\Models\ApplicantProfile;
use Modules\Admission\Models\Application;
use Modules\Admission\Models\ApplicationDocument;
use Modules\Admission\Models\ConflictDeclaration;
use Modules\Admission\Models\DuplicateFlag;
use Modules\Admission\Models\ReviewNote;
use Modules\Admission\Models\VerificationAssignment;
use Modules\Admission\Outbox\OutboxRecorder;
use Modules\Admission\Services\Application\ApplicationService;
use Modules\Admission\Services\Application\DocumentService;
use Modules\Admission\Services\Selection\ScoreService;
use Modules\Admission\Support\Actor;
use Modules\Admission\Support\DocumentReasonCodes;
use Modules\Admission\Support\HistoryRecorder;
use Modules\Admission\Support\PmbPermission;
use Modules\Admission\Support\PmbSettings;
use Modules\Admission\Support\ScopeResolver;
use Modules\Admission\Support\StateTransition;

/**
 * Verifikasi administrasi (docs/pmb/05 §G, SM-06). Status hanya berubah
 * lewat service ini (G.4 butir 1): permission + scope + kepemilikan klaim +
 * optimistic lock + riwayat & audit dalam satu transaksi.
 */
class VerificationService
{
    public function __construct(
        private readonly DocumentService $documents,
        private readonly ApplicationService $applications,
        private readonly HistoryRecorder $history,
        private readonly OutboxRecorder $outbox,
        private readonly PmbSettings $settings,
        private readonly ScopeResolver $scope,
        private readonly ScoreService $scores,
    ) {}

    /**
     * Aplikasi yang siap masuk antrean: terkirim, belum diverifikasi, dan
     * pembayaran terpenuhi (F.3) bila tenant mensyaratkannya.
     *
     * @param  Builder<Application>  $query
     * @return Builder<Application>
     */
    public function scopeQueueable(Builder $query): Builder
    {
        $query->where('form_status', FormStatus::Submitted->value)
            ->where('verification_status', VerificationStatus::NotStarted->value);

        if ($this->settings->bool('pmb.verification_requires_payment')) {
            $query->whereIn('registration_payment_status', [RegistrationPaymentStatus::Paid->value, RegistrationPaymentStatus::NotRequired->value]);
        }

        return $query;
    }

    /** FR-VER-04 mode pull: ambil aplikasi tertua di antrean (klaim terkunci 30 menit). */
    public function claimNext(User $user, array $filters = []): ?Application
    {
        $query = $this->scopeQueueable(Application::query())
            ->whereDoesntHave('activeAssignment')
            ->whereNotIn('id', ConflictDeclaration::query()->where('user_id', $user->getKey())->select('application_id'))
            ->when($filters['wave_path_id'] ?? null, fn ($q, $v) => $q->where('wave_path_id', $v))
            ->when($filters['admission_path_id'] ?? null, fn ($q, $v) => $q->where('admission_path_id', $v))
            ->when($filters['wave_id'] ?? null, fn ($q, $v) => $q->where('wave_id', $v))
            ->orderBy('submitted_at');
        $this->scope->constrainApplications($query, $user, PmbPermission::VERIFICATION_DECIDE);

        foreach ($query->limit(5)->pluck('id') as $id) {
            try {
                return $this->claim(Application::query()->findOrFail($id), $user);
            } catch (ConflictException|UniqueConstraintViolationException) {
                continue;
            }
        }

        return null;
    }

    public function claim(Application $application, User $user, ?User $assignedBy = null, string $mode = 'pull'): Application
    {
        $this->assertScope($application, $user, PmbPermission::VERIFICATION_DECIDE);

        if (ConflictDeclaration::query()->where('user_id', $user->getKey())->where('application_id', $application->getKey())->exists()) {
            throw new ConflictException('Anda memiliki konflik kepentingan dengan pendaftar ini.', 'CONFLICT_OF_INTEREST');
        }

        try {
            return DB::transaction(function () use ($application, $user, $assignedBy, $mode): Application {
                $application = Application::query()->whereKey($application->getKey())->lockForUpdate()->firstOrFail();
                $this->expireStaleClaim($application);

                if ($application->activeAssignment()->exists()) {
                    throw new ConflictException('Aplikasi sedang diperiksa petugas lain.', 'ALREADY_CLAIMED');
                }

                if (! in_array($application->verification_status, [VerificationStatus::NotStarted, VerificationStatus::InReview], true)
                    || ! in_array($application->form_status, [FormStatus::Submitted], true)) {
                    throw new ConflictException('Aplikasi tidak berada di antrean verifikasi.', 'INVALID_TRANSITION');
                }

                if ($application->verification_status === VerificationStatus::NotStarted && $this->settings->bool('pmb.verification_requires_payment') && ! $this->applications->registrationPaid($application)) {
                    throw new ConflictException('Biaya pendaftaran belum lunas.', 'PAYMENT_REQUIRED');
                }

                VerificationAssignment::query()->create([
                    'application_id' => $application->getKey(),
                    'reviewer_user_id' => $user->getKey(),
                    'assigned_by' => $assignedBy?->getKey(),
                    'mode' => $mode,
                    'status' => AssignmentStatus::Active,
                    'claimed_until' => $mode === 'pull' ? now()->addMinutes($this->settings->int('pmb.verification_claim_minutes')) : null,
                ]);

                if ($application->verification_status === VerificationStatus::NotStarted) {
                    $this->transition($application, VerificationStatus::InReview, Actor::user($assignedBy ?? $user), 'CLAIMED');
                    ApplicationDocument::query()->where('application_id', $application->getKey())->where('stage', DocumentStage::Application->value)
                        ->where('status', DocumentStatus::Uploaded->value)->update(['status' => DocumentStatus::InReview->value]);
                }

                return $application->refresh();
            });
        } catch (UniqueConstraintViolationException) {
            throw new ConflictException('Aplikasi sedang diperiksa petugas lain.', 'ALREADY_CLAIMED');
        }
    }

    public function release(Application $application, User $user): void
    {
        DB::transaction(function () use ($application, $user): void {
            $assignment = VerificationAssignment::query()->where('application_id', $application->getKey())->where('status', AssignmentStatus::Active->value)->lockForUpdate()->first();

            if ($assignment === null) {
                return;
            }

            if ((string) $assignment->reviewer_user_id !== (string) $user->getKey() && ! $user->hasPermissionTo(PmbPermission::VERIFICATION_ASSIGN)) {
                abort(403, 'Hanya pemilik klaim atau koordinator yang dapat melepas klaim.');
            }

            $assignment->forceFill(['status' => AssignmentStatus::Released])->save();
            $this->returnToQueue($application->refresh(), Actor::user($user));
        });
    }

    /**
     * FR-VER-04 mode push: bagi aplikasi ke petugas (round-robin atau per jalur).
     *
     * @param  array<int, string>  $applicationIds
     * @param  array<int, string>  $reviewerIds
     * @return array{assigned: int, skipped: int}
     */
    public function assign(array $applicationIds, array $reviewerIds, string $strategy, User $actor): array
    {
        $reviewers = User::query()->whereIn('id', $reviewerIds)->get()->filter(fn (User $u) => $u->hasPermissionTo(PmbPermission::VERIFICATION_DECIDE))->values();

        if ($reviewers->isEmpty()) {
            throw ValidationException::withMessages(['reviewer_ids' => 'Tidak ada petugas verifikasi yang valid.']);
        }

        $applications = Application::query()->whereIn('id', $applicationIds)->orderBy('submitted_at')->get();
        $assigned = 0;
        $skipped = 0;
        $index = 0;
        $byPath = [];

        foreach ($applications as $application) {
            $reviewer = $strategy === 'by_path'
                ? $reviewers[$byPath[$application->admission_path_id] ??= count($byPath) % $reviewers->count()]
                : $reviewers[$index++ % $reviewers->count()];

            try {
                $this->claim($application, $reviewer, $actor, 'push');
                $assigned++;
            } catch (\Throwable) {
                $skipped++;
            }
        }

        return ['assigned' => $assigned, 'skipped' => $skipped];
    }

    /** Klaim pull kedaluwarsa (tanpa aktivitas) dilepas otomatis → kembali ke antrean. */
    public function expireClaims(): int
    {
        $count = 0;

        VerificationAssignment::query()->where('status', AssignmentStatus::Active->value)->where('mode', 'pull')
            ->where('claimed_until', '<', now())->get()
            ->each(function (VerificationAssignment $assignment) use (&$count): void {
                DB::transaction(function () use ($assignment, &$count): void {
                    $assignment->forceFill(['status' => AssignmentStatus::Expired])->save();
                    $this->returnToQueue($assignment->application, Actor::system());
                    $count++;
                });
            });

        return $count;
    }

    /** Setiap aksi petugas memperpanjang klaim (dilepas otomatis bila tidak ada aktivitas). */
    public function touchClaim(Application $application, User $user): void
    {
        VerificationAssignment::query()->where('application_id', $application->getKey())->where('status', AssignmentStatus::Active->value)
            ->where('reviewer_user_id', $user->getKey())->where('mode', 'pull')
            ->update(['claimed_until' => now()->addMinutes($this->settings->int('pmb.verification_claim_minutes'))]);
    }

    /**
     * Syarat BR-22 yang belum terpenuhi.
     *
     * @return array<int, string>
     */
    public function unmetForVerification(Application $application): array
    {
        $unmet = [];
        $context = $this->applications->ruleContext($application);

        foreach ($this->documents->unmetMandatory($application, DocumentStage::Application, true, $context) as $doc) {
            $unmet[] = 'DOCUMENT_NOT_APPROVED:'.$doc['document_type'];
        }

        if (! $this->applications->registrationPaid($application)) {
            $unmet[] = 'PAYMENT_NOT_SATISFIED';
        }

        if ($this->hasOpenDuplicateFlag($application)) {
            $unmet[] = 'OPEN_DUPLICATE_FLAG';
        }

        $pendingDocs = ApplicationDocument::query()->where('application_id', $application->getKey())->where('stage', DocumentStage::Application->value)
            ->whereIn('status', [DocumentStatus::RevisionRequested->value, DocumentStatus::Rejected->value])->where('is_mandatory', true)->count();

        if ($pendingDocs > 0 && ! in_array('DOCUMENT_REVISION_PENDING', $unmet, true)) {
            $unmet[] = 'DOCUMENT_REVISION_PENDING';
        }

        return array_values(array_unique($unmet));
    }

    /** 11 §A.18.4: verifikasi → verified, selection eligible, form & dokumen terkunci. */
    public function verify(Application $application, int $lockVersion, ?string $note, User $user): Application
    {
        $this->assertCanDecide($application, $user);
        $unmet = $this->unmetForVerification($application);

        if ($unmet !== []) {
            throw new InvalidTransitionException('Aplikasi belum memenuhi syarat verifikasi.', $unmet);
        }

        $application = DB::transaction(function () use ($application, $lockVersion, $note, $user): Application {
            $application = $this->lockForDecision($application, $lockVersion);
            $actor = Actor::user($user);

            $this->transition($application, VerificationStatus::Verified, $actor, null, $note);
            $application->forceFill(['verified_at' => now()])->save();

            $formFrom = $application->form_status;
            StateTransition::assert($formFrom, FormStatus::Locked, 'formulir');
            $application->forceFill(['form_status' => FormStatus::Locked])->save();
            $this->history->record($application, Lifecycle::Form, $formFrom, FormStatus::Locked, $actor, 'VERIFIED');

            StateTransition::assert($application->selection_status, SelectionStatus::Eligible, 'seleksi');
            $selectionFrom = $application->selection_status;
            $application->forceFill(['selection_status' => SelectionStatus::Eligible])->save();
            $this->history->record($application, Lifecycle::Selection, $selectionFrom, SelectionStatus::Eligible, $actor, 'VERIFIED');

            $this->documents->lockStage($application, DocumentStage::Application);
            $this->completeAssignment($application);

            if ($note) {
                $this->addNote($application, 'internal', $note, $user, 'verification');
            }

            $this->outbox->notifyApplicant($application->account, 'pmb.application.verified', ['registration_number' => $application->registration_number], aggregate: $application);

            return $application->refresh();
        });

        // FR-SEL-02: nilai rapor/prestasi/administrasi dihitung otomatis setelah verifikasi.
        $this->scores->computeDerived($application);

        return $application;
    }

    /**
     * Minta perbaikan (FR-VER-03, BR-24): batas = min(sekarang + window, form_lock_at).
     *
     * @param  array{fields?: array<int, string>, documents?: array<int, string>}  $targets
     */
    public function requestRevision(Application $application, array $targets, string $noteForApplicant, ?string $dueAt, int $lockVersion, User $user): Application
    {
        $this->assertCanDecide($application, $user);
        $formLock = CarbonImmutable::parse($application->wave->form_lock_at);

        if (now()->gte($formLock)) {
            throw new DomainRuleException('Batas perbaikan formulir gelombang ini sudah lewat.', 'DEADLINE_PASSED', 422);
        }

        $fields = array_values(array_unique((array) ($targets['fields'] ?? [])));
        $documents = array_values(array_unique((array) ($targets['documents'] ?? [])));

        if ($fields === [] && $documents === []) {
            throw ValidationException::withMessages(['targets' => 'Pilih minimal satu field atau dokumen yang perlu diperbaiki.']);
        }

        $due = $dueAt ? CarbonImmutable::parse($dueAt) : now()->addHours($this->settings->int('pmb.revision_window_hours'));
        $due = $due->gt($formLock) ? $formLock : $due;

        return DB::transaction(function () use ($application, $fields, $documents, $noteForApplicant, $due, $lockVersion, $user): Application {
            $application = $this->lockForDecision($application, $lockVersion);
            $actor = Actor::user($user);

            $this->transition($application, VerificationStatus::RevisionRequested, $actor, null, $noteForApplicant);

            $formFrom = $application->form_status;
            StateTransition::assert($formFrom, FormStatus::RevisionRequested, 'formulir');
            $application->forceFill([
                'form_status' => FormStatus::RevisionRequested,
                'revision_targets' => ['fields' => $fields, 'documents' => $documents],
                'revision_due_at' => $due,
            ])->save();
            $this->history->record($application, Lifecycle::Form, $formFrom, FormStatus::RevisionRequested, $actor);

            foreach (ApplicationDocument::query()->where('application_id', $application->getKey())->where('stage', DocumentStage::Application->value)->with('documentType')->get() as $document) {
                $targeted = in_array($document->getKey(), $documents, true) || in_array($document->documentType?->code, $documents, true);

                if ($targeted && in_array($document->status, [DocumentStatus::InReview, DocumentStatus::Uploaded, DocumentStatus::Missing], true)) {
                    if ($document->status !== DocumentStatus::Missing) {
                        $document->forceFill(['status' => DocumentStatus::RevisionRequested, 'locked_at' => null])->save();
                    }
                }
            }

            ReviewNote::query()->create([
                'application_id' => $application->getKey(),
                'context' => 'verification',
                'visibility' => 'applicant',
                'body' => $noteForApplicant,
                'target_fields' => ['fields' => $fields, 'documents' => $documents],
                'due_at' => $due,
                'author_id' => $user->getKey(),
            ]);

            $this->completeAssignment($application);
            $this->outbox->notifyApplicant($application->account, 'pmb.application.revision_requested', ['deadline' => $due->setTimezone($this->timezone())->format('d-m-Y H:i')], aggregate: $application);

            return $application->refresh();
        });
    }

    /** Tolak administrasi (BR-23): kode alasan + catatan untuk pendaftar wajib. */
    public function reject(Application $application, string $reasonCode, string $noteForApplicant, ?string $internalNote, int $lockVersion, User $user): Application
    {
        $this->assertCanDecide($application, $user);

        if (! in_array($reasonCode, DocumentReasonCodes::APPLICATION_REJECTION, true)) {
            throw ValidationException::withMessages(['reason_code' => 'Kode alasan penolakan tidak dikenal.']);
        }

        return DB::transaction(function () use ($application, $reasonCode, $noteForApplicant, $internalNote, $lockVersion, $user): Application {
            $application = $this->lockForDecision($application, $lockVersion);
            $this->applyRejection($application, $reasonCode, $noteForApplicant, $internalNote, Actor::user($user), $user);

            return $application->refresh();
        });
    }

    /**
     * Override Admin PMB (G.3): verified → in_review (hanya sebelum dinilai)
     * atau rejected → in_review (pembatalan penolakan), alasan wajib.
     */
    public function reopen(Application $application, string $reason, User $user): Application
    {
        return DB::transaction(function () use ($application, $reason, $user): Application {
            $application = Application::query()->whereKey($application->getKey())->lockForUpdate()->firstOrFail();
            $actor = Actor::user($user);

            if ($application->verification_status === VerificationStatus::Verified
                && ! in_array($application->selection_status, [SelectionStatus::Eligible, SelectionStatus::Scheduled], true)) {
                throw new InvalidTransitionException('Aplikasi sudah dinilai; gunakan koreksi hasil.');
            }

            if (! in_array($application->verification_status, [VerificationStatus::Verified, VerificationStatus::Rejected], true)) {
                throw InvalidTransitionException::between('verifikasi', $application->verification_status->value, VerificationStatus::InReview->value);
            }

            $this->transition($application, VerificationStatus::InReview, $actor, 'OVERRIDE', $reason);

            if ($application->form_status === FormStatus::Locked) {
                $application->forceFill(['form_status' => FormStatus::Submitted])->save();
                $this->history->record($application, Lifecycle::Form, FormStatus::Locked, FormStatus::Submitted, $actor, 'OVERRIDE', $reason);
            }

            if ($application->selection_status !== SelectionStatus::NotEligible) {
                $from = $application->selection_status;
                $application->forceFill(['selection_status' => SelectionStatus::NotEligible])->save();
                $this->history->record($application, Lifecycle::Selection, $from, SelectionStatus::NotEligible, $actor, 'OVERRIDE', $reason);
            }

            $application->forceFill(['verified_at' => null, 'rejection_reason_code' => null])->save();
            ApplicationDocument::query()->where('application_id', $application->getKey())->where('stage', DocumentStage::Application->value)->update(['locked_at' => null]);

            VerificationAssignment::query()->create([
                'application_id' => $application->getKey(),
                'reviewer_user_id' => $user->getKey(),
                'assigned_by' => $user->getKey(),
                'mode' => 'push',
                'status' => AssignmentStatus::Active,
            ]);

            return $application->refresh();
        });
    }

    /** Job BR-24: revisi lewat batas → rejected REVISION_EXPIRED. */
    public function expireRevisions(): int
    {
        $count = 0;

        Application::query()->where('verification_status', VerificationStatus::RevisionRequested->value)
            ->whereNotNull('revision_due_at')->where('revision_due_at', '<', now())->get()
            ->each(function (Application $application) use (&$count): void {
                DB::transaction(function () use ($application, &$count): void {
                    $application = Application::query()->whereKey($application->getKey())->lockForUpdate()->first();

                    if ($application?->verification_status !== VerificationStatus::RevisionRequested) {
                        return;
                    }

                    $this->applyRejection($application, 'REVISION_EXPIRED', 'Batas waktu perbaikan telah lewat tanpa pengiriman ulang.', null, Actor::system(), null);
                    $count++;
                });
            });

        return $count;
    }

    /**
     * FR-VER-05: hanya aksi AMAN — aplikasi jalur `bulk_verifiable` yang semua
     * dokumennya sudah approved satu per satu & tanpa flag. Maks 100.
     *
     * @param  array<int, string>  $ids
     * @return array{succeeded: array<int, string>, failed: array<int, array{id: string, code: string}>}
     */
    public function bulkVerify(array $ids, User $user): array
    {
        if (count($ids) > 100) {
            throw ValidationException::withMessages(['application_ids' => 'Maksimal 100 aplikasi per aksi.']);
        }

        $succeeded = [];
        $failed = [];
        $batchId = (string) \Illuminate\Support\Str::ulid();

        foreach (Application::query()->with('wavePath')->whereIn('id', $ids)->get() as $application) {
            try {
                if (! $application->wavePath->bulk_verifiable) {
                    throw new DomainRuleException('Jalur tidak mengizinkan verifikasi massal.', 'NOT_BULK_VERIFIABLE');
                }

                if ($application->verification_status === VerificationStatus::NotStarted) {
                    $application = $this->claim($application, $user, $user, 'push');
                }

                $this->verify($application, $application->lock_version, 'Verifikasi massal batch '.$batchId, $user);
                $succeeded[] = (string) $application->getKey();
            } catch (\Throwable $e) {
                $failed[] = ['id' => (string) $application->getKey(), 'code' => match (true) {
                    $e instanceof InvalidTransitionException => implode(',', $e->unmet) ?: 'INVALID_TRANSITION',
                    $e instanceof DomainRuleException => $e->errorCode,
                    $e instanceof ConflictException => $e->errorCode ?? 'CONFLICT',
                    default => 'FAILED',
                }];
            }
        }

        return ['succeeded' => $succeeded, 'failed' => $failed, 'batch_id' => $batchId];
    }

    /**
     * Persetujuan massal pas foto setelah dilihat di galeri (FR-VER-05 a).
     *
     * @param  array<int, string>  $documentIds
     * @return array{approved: int}
     */
    public function bulkApprovePhotos(array $documentIds, User $user): array
    {
        if (count($documentIds) > 100) {
            throw ValidationException::withMessages(['document_ids' => 'Maksimal 100 dokumen per aksi.']);
        }

        $approved = 0;

        ApplicationDocument::query()->with('documentType')->whereIn('id', $documentIds)
            ->whereHas('documentType', fn ($q) => $q->where('code', 'PHOTO'))
            ->whereIn('status', [DocumentStatus::InReview->value, DocumentStatus::Uploaded->value])->get()
            ->each(function (ApplicationDocument $document) use ($user, &$approved): void {
                $this->assertScope($document->application, $user, PmbPermission::VERIFICATION_DECIDE);
                $this->documents->review($document, ['decision' => 'approved', 'version_id' => (string) $document->current_version_id], $user);
                $approved++;
            });

        return ['approved' => $approved];
    }

    public function addNote(Application $application, string $visibility, string $body, User $user, string $context = 'verification'): ReviewNote
    {
        return ReviewNote::query()->create([
            'application_id' => $application->getKey(),
            'context' => $context,
            'visibility' => $visibility === 'applicant' ? 'applicant' : 'internal',
            'body' => $body,
            'author_id' => $user->getKey(),
        ]);
    }

    public function declareConflict(Application $application, User $user, string $reason): ConflictDeclaration
    {
        return ConflictDeclaration::query()->firstOrCreate(
            ['user_id' => $user->getKey(), 'application_id' => $application->getKey()],
            ['university_id' => $application->university_id, 'reason' => $reason],
        );
    }

    // ------------------------------------------------------------------ Flag duplikat

    public function hasOpenDuplicateFlag(Application $application): bool
    {
        return DuplicateFlag::query()->where('status', DuplicateFlagStatus::Open->value)
            ->where(fn ($q) => $q->where('applicant_account_id', $application->applicant_account_id)->orWhere('related_account_id', $application->applicant_account_id))
            ->exists();
    }

    /**
     * 10 §5: konfirmasi duplikat → nonaktifkan akun sekunder & tarik
     * aplikasinya (DUPLICATE_APPLICATION); bukan duplikat → tutup flag.
     */
    public function resolveDuplicateFlag(DuplicateFlag $flag, string $status, string $note, User $user, ?string $secondaryAccountId = null): DuplicateFlag
    {
        $target = DuplicateFlagStatus::from($status);

        if ($flag->status !== DuplicateFlagStatus::Open) {
            throw new ConflictException('Flag sudah diselesaikan.', 'ALREADY_DECIDED');
        }

        return DB::transaction(function () use ($flag, $target, $note, $user, $secondaryAccountId): DuplicateFlag {
            if (in_array($target, [DuplicateFlagStatus::ConfirmedDuplicate, DuplicateFlagStatus::Merged], true)) {
                $secondary = ApplicantAccount::query()->findOrFail($secondaryAccountId ?? $flag->applicant_account_id);

                foreach (Application::query()->active()->where('applicant_account_id', $secondary->getKey())->get() as $application) {
                    if ($application->form_status === FormStatus::Draft) {
                        $this->applications->discardDraft($application, Actor::user($user), 'DUPLICATE_APPLICATION');
                    } elseif (! $application->currentDecision()->published()->exists()) {
                        $this->applications->withdraw($application, 'DUPLICATE_APPLICATION', Actor::user($user));
                    }
                }

                if ($secondary->status->canTransitionTo(AccountStatus::Deactivated)) {
                    $secondary->forceFill(['status' => AccountStatus::Deactivated, 'deactivated_at' => now()])->save();
                    $secondary->tokens()->delete();
                }
            }

            $flag->forceFill(['status' => $target, 'resolution_note' => $note, 'resolved_by' => $user->getKey(), 'resolved_at' => now()])->save();
            $this->history->action($flag, \Modules\AuditLog\Enums\AuditAction::Updated, ['status' => $target->value], $note);

            return $flag->refresh();
        });
    }

    /**
     * Job harian heuristik (04 §C.3): nama ternormalisasi + tanggal lahir
     * (+ sekolah) sama lintas akun → flag `heuristic_match` untuk ditinjau.
     */
    public function detectDuplicates(): int
    {
        $created = 0;
        $profiles = ApplicantProfile::query()->whereNotNull('full_name')->whereNotNull('birth_date')->get(['applicant_account_id', 'full_name', 'birth_date']);
        $groups = $profiles->groupBy(fn ($p) => mb_strtolower(preg_replace('/[^\p{L}]/u', '', (string) $p->full_name)).'|'.$p->birth_date?->toDateString());

        foreach ($groups as $group) {
            if ($group->count() < 2) {
                continue;
            }

            $accounts = $group->pluck('applicant_account_id')->values();

            for ($i = 1; $i < $accounts->count(); $i++) {
                $exists = DuplicateFlag::query()->where('type', DuplicateFlagType::HeuristicMatch->value)
                    ->where(fn ($q) => $q->where(fn ($a) => $a->where('applicant_account_id', $accounts[$i])->where('related_account_id', $accounts[0]))
                        ->orWhere(fn ($b) => $b->where('applicant_account_id', $accounts[0])->where('related_account_id', $accounts[$i])))->exists();

                if ($exists) {
                    continue;
                }

                $schoolA = ApplicantAccount::query()->find($accounts[0])?->educations()->value('institution_name');
                $schoolB = ApplicantAccount::query()->find($accounts[$i])?->educations()->value('institution_name');
                $sameSchool = $schoolA !== null && $schoolB !== null && mb_strtolower(trim($schoolA)) === mb_strtolower(trim($schoolB));

                DuplicateFlag::query()->create([
                    'applicant_account_id' => $accounts[$i],
                    'related_account_id' => $accounts[0],
                    'type' => DuplicateFlagType::HeuristicMatch,
                    'similarity_score' => $sameSchool ? 95 : 80,
                    'status' => DuplicateFlagStatus::Open,
                    'note' => 'Nama & tanggal lahir sama'.($sameSchool ? ', sekolah sama' : ''),
                ]);
                $created++;
            }
        }

        return $created;
    }

    // ------------------------------------------------------------------ Helper

    private function applyRejection(Application $application, string $reasonCode, string $noteForApplicant, ?string $internalNote, Actor $actor, ?User $user): void
    {
        $this->transition($application, VerificationStatus::Rejected, $actor, $reasonCode, $internalNote ?? $noteForApplicant);
        $formFrom = $application->form_status;

        if ($formFrom->canTransitionTo(FormStatus::Locked)) {
            $application->forceFill(['form_status' => FormStatus::Locked])->save();
            $this->history->record($application, Lifecycle::Form, $formFrom, FormStatus::Locked, $actor, $reasonCode);
        }

        $application->forceFill(['rejection_reason_code' => $reasonCode, 'revision_targets' => null, 'revision_due_at' => null])->save();

        // Catatan sistem (job kedaluwarsa revisi) tidak memiliki penulis staf: author_id null.
        ReviewNote::query()->create([
            'application_id' => $application->getKey(),
            'context' => 'verification',
            'visibility' => 'applicant',
            'body' => $noteForApplicant,
            'author_id' => $user?->getKey(),
        ]);

        if ($internalNote && $user) {
            $this->addNote($application, 'internal', $internalNote, $user);
        }

        $this->completeAssignment($application);
        $this->outbox->notifyApplicant($application->account, 'pmb.application.rejected', ['registration_number' => $application->registration_number], aggregate: $application);
    }

    private function transition(Application $application, VerificationStatus $to, Actor $actor, ?string $reasonCode = null, ?string $note = null): void
    {
        $from = $application->verification_status;
        StateTransition::assert($from, $to, 'verifikasi');
        $application->forceFill(['verification_status' => $to, 'lock_version' => $application->lock_version + 1])->save();
        $this->history->record($application, Lifecycle::Verification, $from, $to, $actor, $reasonCode, $note);
    }

    private function returnToQueue(Application $application, Actor $actor): void
    {
        if ($application->verification_status === VerificationStatus::InReview && $application->form_status === FormStatus::Submitted && $application->verified_at === null
            && ! $application->histories()->where('lifecycle', Lifecycle::Verification->value)->where('to_status', VerificationStatus::RevisionRequested->value)->exists()) {
            $this->transition($application, VerificationStatus::NotStarted, $actor, 'RELEASED');
            ApplicationDocument::query()->where('application_id', $application->getKey())->where('status', DocumentStatus::InReview->value)->update(['status' => DocumentStatus::Uploaded->value]);
        }
    }

    private function completeAssignment(Application $application): void
    {
        VerificationAssignment::query()->where('application_id', $application->getKey())->where('status', AssignmentStatus::Active->value)
            ->get()->each(fn (VerificationAssignment $a) => $a->forceFill(['status' => AssignmentStatus::Completed])->save());
    }

    private function expireStaleClaim(Application $application): void
    {
        VerificationAssignment::query()->where('application_id', $application->getKey())->where('status', AssignmentStatus::Active->value)
            ->where('mode', 'pull')->where('claimed_until', '<', now())->get()
            ->each(fn (VerificationAssignment $a) => $a->forceFill(['status' => AssignmentStatus::Expired])->save());
    }

    private function lockForDecision(Application $application, int $lockVersion): Application
    {
        $locked = Application::query()->whereKey($application->getKey())->lockForUpdate()->firstOrFail();

        if ($locked->lock_version !== $lockVersion) {
            throw new DomainRuleException('Data telah diubah petugas lain. Muat ulang.', 'VERSION_CONFLICT', 409, ['current_version' => $locked->lock_version]);
        }

        if ($locked->verification_status !== VerificationStatus::InReview) {
            throw InvalidTransitionException::between('verifikasi', $locked->verification_status->value, 'keputusan');
        }

        return $locked;
    }

    /** G.4 butir 2: permission + scope + kepemilikan klaim (atau koordinator/Admin PMB). */
    private function assertCanDecide(Application $application, User $user): void
    {
        $this->assertScope($application, $user, PmbPermission::VERIFICATION_DECIDE);
        $assignment = $application->activeAssignment()->first();
        $isCoordinator = $user->hasPermissionTo(PmbPermission::VERIFICATION_ASSIGN);

        if (! $isCoordinator && ($assignment === null || (string) $assignment->reviewer_user_id !== (string) $user->getKey())) {
            throw new ConflictException('Klaim aplikasi ini terlebih dahulu (atau klaim Anda sudah kedaluwarsa).', 'NOT_CLAIMED');
        }

        if (ConflictDeclaration::query()->where('user_id', $user->getKey())->where('application_id', $application->getKey())->exists()) {
            throw new ConflictException('Anda memiliki konflik kepentingan dengan pendaftar ini.', 'CONFLICT_OF_INTEREST');
        }

        $this->touchClaim($application, $user);
    }

    private function assertScope(Application $application, User $user, string $permission): void
    {
        $programIds = $this->scope->programIdsFor($user, $permission);

        if ($programIds !== null && ! $application->choices()->whereIn('study_program_id', $programIds === [] ? ['-'] : $programIds)->exists()) {
            abort(404);
        }
    }

    private function timezone(): string
    {
        return (string) (\Modules\Tenancy\Models\University::query()->whereKey(app(\App\Support\Tenancy\TenantContext::class)->universityId())->value('timezone') ?: config('app.timezone'));
    }
}
