<?php

namespace Modules\Admission\Services\ReRegistration;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Admission\Enums\ChangeRequestStatus;
use Modules\Admission\Enums\ChangeRequestType;
use Modules\Admission\Enums\CheckEvaluation;
use Modules\Admission\Enums\CheckStatus;
use Modules\Admission\Enums\ConsentType;
use Modules\Admission\Enums\ConversionStatus;
use Modules\Admission\Enums\Decision;
use Modules\Admission\Enums\DecisionSource;
use Modules\Admission\Enums\DocumentStage;
use Modules\Admission\Enums\DocumentStatus;
use Modules\Admission\Enums\FormStatus;
use Modules\Admission\Enums\InvoiceType;
use Modules\Admission\Enums\Lifecycle;
use Modules\Admission\Enums\PmbInvoiceStatus;
use Modules\Admission\Enums\PublicationStatus;
use Modules\Admission\Enums\ReRegistrationStatus;
use Modules\Admission\Enums\TransactionStatus;
use Modules\Admission\Exceptions\DomainRuleException;
use Modules\Admission\Exceptions\InvalidTransitionException;
use Modules\Admission\Models\AdmissionDecision;
use Modules\Admission\Models\ApplicantProfile;
use Modules\Admission\Models\Application;
use Modules\Admission\Models\ApplicationAnswer;
use Modules\Admission\Models\ApplicationDocument;
use Modules\Admission\Models\ChangeRequest;
use Modules\Admission\Models\DocumentRequirement;
use Modules\Admission\Models\DuplicateFlag;
use Modules\Admission\Models\FormField;
use Modules\Admission\Models\ManualPaymentProof;
use Modules\Admission\Models\PmbInvoice;
use Modules\Admission\Models\Refund;
use Modules\Admission\Models\ReRegistration;
use Modules\Admission\Models\ReRegistrationCheck;
use Modules\Admission\Models\StudentConversion;
use Modules\Admission\Outbox\OutboxRecorder;
use Modules\Admission\Services\Account\AccountService;
use Modules\Admission\Services\Account\ProfileService;
use Modules\Admission\Services\Announcement\DecisionService;
use Modules\Admission\Services\Announcement\PublicationService;
use Modules\Admission\Services\Application\ApplicationService;
use Modules\Admission\Services\Application\DocumentService;
use Modules\Admission\Services\Approval\PmbApprovalService;
use Modules\Admission\Services\Payment\InvoiceService;
use Modules\Admission\Services\Verification\VerificationService;
use Modules\Admission\Support\Actor;
use Modules\Admission\Support\HistoryRecorder;
use Modules\Admission\Support\Money;
use Modules\Admission\Support\PmbSettings;
use Modules\Admission\Support\StateTransition;
use Modules\AuditLog\Enums\AuditAction;

/**
 * Daftar ulang (docs/pmb/07 §K, SM-10, BR-33..35): "diterima" ≠ mahasiswa;
 * persetujuan final memicu konversi lewat outbox (KD-06).
 */
class ReRegistrationService
{
    /** Field PDDikti wajib yang dikumpulkan saat daftar ulang (Q-REG-05 default). */
    public const PDDIKTI_PROFILE_FIELDS = ['religion', 'mother_name', 'nationality', 'address_line', 'village', 'district', 'city', 'province', 'region_code'];

    public const PDDIKTI_EXTRA_FIELDS = ['residence_type', 'transportation', 'kps_recipient'];

    public const CHECKS = [
        'OFFER_ACCEPTED' => 'auto',
        'PDDIKTI_DATA_COMPLETE' => 'auto',
        'GUARDIAN_DATA_COMPLETE' => 'auto',
        'FINAL_DOCUMENTS_APPROVED' => 'auto',
        'HEALTH_CLEARED' => 'auto',
        'PAYMENT_MINIMUM_MET' => 'auto',
        'ORIGINAL_DOCUMENTS_CHECKED' => 'manual',
        'NO_OPEN_DUPLICATE_FLAG' => 'auto',
    ];

    public function __construct(
        private readonly HistoryRecorder $history,
        private readonly OutboxRecorder $outbox,
        private readonly InvoiceService $invoices,
        private readonly DocumentService $documents,
        private readonly ProfileService $profiles,
        private readonly PmbSettings $settings,
        private readonly DecisionService $decisions,
        private readonly PublicationService $publication,
        private readonly PmbApprovalService $approvals,
    ) {}

    /**
     * Tahap 1–2: terima tawaran — dalam jendela daftar ulang; tawaran lain di
     * periode yang sama otomatis ditarik (A-17); tagihan biaya awal dibuat.
     *
     * @param  array<int, string>  $declarations
     * @return array{re_registration: ReRegistration, withdrawn_application_ids: array<int, string>}
     */
    public function accept(ReRegistration $reRegistration, array $declarations, int $lockVersion, ?string $ip, ?string $userAgent, Actor $actor): array
    {
        $application = $reRegistration->application;
        $opensAt = CarbonImmutable::parse($application->wave->re_registration_opens_at);

        if (now()->lt($opensAt) && $reRegistration->decision?->source !== DecisionSource::WaitlistPromotion) {
            throw new DomainRuleException('Daftar ulang belum dibuka (mulai '.$opensAt->toDateTimeString().').', 'NOT_OPEN', 422);
        }

        if ($reRegistration->effectiveDeadline()->isPast()) {
            throw new DomainRuleException('Batas daftar ulang telah lewat.', 'DEADLINE_PASSED', 422);
        }

        $withdrawn = [];

        $result = DB::transaction(function () use ($reRegistration, $declarations, $lockVersion, $ip, $userAgent, $actor, &$withdrawn): ReRegistration {
            $reRegistration = $this->lock($reRegistration, $lockVersion);
            StateTransition::assert($reRegistration->status, ReRegistrationStatus::Confirmed, 'daftar ulang');

            $decision = AdmissionDecision::query()->where('application_id', $reRegistration->application_id)->where('is_current', true)->first();

            if ($decision?->decision !== Decision::Accepted || $decision->publication_status !== PublicationStatus::Published) {
                throw new InvalidTransitionException('Tidak ada tawaran penerimaan yang berlaku.');
            }

            $application = $reRegistration->application;
            $from = $reRegistration->status;
            $reRegistration->forceFill([
                'status' => ReRegistrationStatus::Confirmed,
                'confirmed_at' => now(),
                'declarations' => array_values($declarations),
                'lock_version' => $reRegistration->lock_version + 1,
            ])->save();

            app(AccountService::class)->recordConsent($application->account, ConsentType::AcademicTerms, null, null, $ip, $userAgent, $application->getKey());
            $this->history->record($application, Lifecycle::ReRegistration, $from, ReRegistrationStatus::Confirmed, $actor);

            $withdrawn = $this->withdrawOtherOffers($application, $actor);
            $this->documents->syncRequirements($application, DocumentStage::ReRegistration);
            $this->ensureChecks($reRegistration);
            $this->invoices->createInitialTuitionInvoice($application, $decision, $reRegistration->effectiveDeadline());

            $this->outbox->notifyApplicant($application->account, 'pmb.re_registration.confirmed', ['deadline' => $reRegistration->effectiveDeadline()->toDateTimeString()], aggregate: $reRegistration);

            return $reRegistration->refresh();
        });

        return ['re_registration' => $result, 'withdrawn_application_ids' => $withdrawn];
    }

    /** Tolak tawaran (terminal) — kursi dibebaskan (K.5). */
    public function decline(ReRegistration $reRegistration, ?string $reason, Actor $actor): ReRegistration
    {
        return DB::transaction(function () use ($reRegistration, $reason, $actor): ReRegistration {
            $reRegistration = ReRegistration::query()->whereKey($reRegistration->getKey())->lockForUpdate()->firstOrFail();
            $from = $reRegistration->status;
            StateTransition::assert($from, ReRegistrationStatus::Declined, 'daftar ulang');

            $reRegistration->forceFill(['status' => ReRegistrationStatus::Declined, 'declined_at' => now(), 'decline_reason' => $reason ? mb_substr($reason, 0, 255) : null])->save();
            $this->history->record($reRegistration->application, Lifecycle::ReRegistration, $from, ReRegistrationStatus::Declined, $actor, 'DECLINED', $reason);
            $this->decisions->releaseSeatFor($reRegistration->decision);
            $this->outbox->notifyApplicant($reRegistration->application->account, 'pmb.re_registration.declined', [], aggregate: $reRegistration);

            return $reRegistration->refresh();
        });
    }

    /**
     * Tahap 3–4: data lanjutan PDDikti & orang tua/wali + field dinamis tahap
     * `re_registration`. Aktivitas pertama → in_progress.
     *
     * @param  array<string, mixed>  $data
     */
    public function saveData(ReRegistration $reRegistration, array $data, int $lockVersion, Actor $actor): ReRegistration
    {
        return DB::transaction(function () use ($reRegistration, $data, $lockVersion, $actor): ReRegistration {
            $reRegistration = $this->lock($reRegistration, $lockVersion);
            $this->assertEditable($reRegistration, 'data');
            $application = $reRegistration->application;

            $profileValues = Arr::only($data, self::PDDIKTI_PROFILE_FIELDS);
            $extra = Arr::only($data, self::PDDIKTI_EXTRA_FIELDS);

            if ($extra !== []) {
                $current = (array) ($application->account->profile?->pddikti_extra ?? []);
                $profileValues['pddikti_extra'] = [...$current, ...$extra];
            }

            if ($profileValues !== []) {
                $this->profiles->saveProfile($application->account, $profileValues, null, byStaff: true);
            }

            if (array_key_exists('almamater_size', $data)) {
                $reRegistration->forceFill(['almamater_size' => $data['almamater_size']]);
            }

            foreach ((array) ($data['answers'] ?? []) as $key => $value) {
                $field = FormField::query()->where('form_template_id', $application->form_template_id)->where('stage', 're_registration')->where('key', $key)->first();

                if ($field !== null) {
                    ApplicationAnswer::query()->updateOrCreate(
                        ['application_id' => $application->getKey(), 'form_field_id' => $field->getKey()],
                        ['university_id' => $application->university_id, 'value_text' => is_array($value) ? null : (string) $value, 'value_json' => is_array($value) ? $value : null],
                    );
                }
            }

            $this->markInProgress($reRegistration, $actor);
            $reRegistration->forceFill(['lock_version' => $reRegistration->lock_version + 1])->save();

            return $reRegistration->refresh();
        });
    }

    /**
     * Evaluasi checklist otomatis (K.2 kode checklist). Manual tetap seperti
     * yang dicentang petugas.
     *
     * @return array<string, array{status: string, evaluation: string, note: ?string}>
     */
    public function evaluateChecks(ReRegistration $reRegistration): array
    {
        $this->ensureChecks($reRegistration);
        $application = $reRegistration->application()->with(['account.profile', 'account.guardians', 'documents.documentType'])->first();
        $profile = $application->account->profile;
        $results = [];

        foreach (ReRegistrationCheck::query()->where('re_registration_id', $reRegistration->getKey())->get() as $check) {
            if ($check->evaluation === CheckEvaluation::Manual) {
                $results[$check->check_code] = ['status' => $check->status->value, 'evaluation' => 'manual', 'note' => $check->note];

                continue;
            }

            [$status, $note] = match ($check->check_code) {
                'OFFER_ACCEPTED' => [$reRegistration->confirmed_at !== null ? CheckStatus::Passed : CheckStatus::Pending, null],
                'PDDIKTI_DATA_COMPLETE' => $this->pddiktiComplete($profile),
                'GUARDIAN_DATA_COMPLETE' => $this->guardiansComplete($application),
                'FINAL_DOCUMENTS_APPROVED' => $this->finalDocuments($application, true),
                'HEALTH_CLEARED' => $this->healthCleared($application),
                'PAYMENT_MINIMUM_MET' => $this->paymentMinimum($application),
                'NO_OPEN_DUPLICATE_FLAG' => [DuplicateFlag::query()->where('status', 'open')->where(fn ($q) => $q->where('applicant_account_id', $application->applicant_account_id)->orWhere('related_account_id', $application->applicant_account_id))->exists() ? CheckStatus::Failed : CheckStatus::Passed, null],
                default => [CheckStatus::Pending, null],
            };

            $check->forceFill(['status' => $status, 'note' => $note, 'checked_at' => now()])->save();
            $results[$check->check_code] = ['status' => $status->value, 'evaluation' => 'auto', 'note' => $note];
        }

        return $results;
    }

    /**
     * Tahap 9: "Kirim daftar ulang" — semua checklist pendaftar hijau (data,
     * orang tua, dokumen terunggah, pembayaran minimum). Persetujuan dokumen &
     * pemeriksaan asli dilakukan petugas setelahnya.
     */
    public function submit(ReRegistration $reRegistration, int $lockVersion, Actor $actor): ReRegistration
    {
        $checks = $this->evaluateChecks($reRegistration);
        $application = $reRegistration->application;
        $unmet = [];

        foreach (['OFFER_ACCEPTED', 'PDDIKTI_DATA_COMPLETE', 'GUARDIAN_DATA_COMPLETE', 'PAYMENT_MINIMUM_MET', 'NO_OPEN_DUPLICATE_FLAG'] as $code) {
            if (! in_array($checks[$code]['status'] ?? 'pending', ['passed', 'not_applicable'], true)) {
                $unmet[] = $code;
            }
        }

        [$uploaded] = $this->finalDocuments($application, false);

        if ($uploaded !== CheckStatus::Passed) {
            $unmet[] = 'FINAL_DOCUMENTS_UPLOADED';
        }

        if ($unmet !== []) {
            throw new DomainRuleException('Daftar ulang belum lengkap.', 'INCOMPLETE_RE_REGISTRATION', 422, ['unmet_checks' => $unmet]);
        }

        return DB::transaction(function () use ($reRegistration, $lockVersion, $actor): ReRegistration {
            $reRegistration = $this->lock($reRegistration, $lockVersion);

            if ($reRegistration->status === ReRegistrationStatus::Confirmed) {
                $this->markInProgress($reRegistration, $actor);
            }

            $from = $reRegistration->status;
            StateTransition::assert($from, ReRegistrationStatus::Submitted, 'daftar ulang');
            $reRegistration->forceFill(['status' => ReRegistrationStatus::Submitted, 'submitted_at' => now(), 'revision_targets' => null, 'revision_due_at' => null, 'lock_version' => $reRegistration->lock_version + 1])->save();
            $this->history->record($reRegistration->application, Lifecycle::ReRegistration, $from, ReRegistrationStatus::Submitted, $actor);
            $this->outbox->notifyApplicant($reRegistration->application->account, 'pmb.re_registration.submitted', [], aggregate: $reRegistration);

            return $reRegistration->refresh();
        });
    }

    /** Tahap 10: petugas menandai checklist manual (pemeriksaan dokumen asli dsb.). */
    public function markCheck(ReRegistration $reRegistration, string $code, string $status, ?string $note, User $officer): ReRegistrationCheck
    {
        $check = ReRegistrationCheck::query()->where('re_registration_id', $reRegistration->getKey())->where('check_code', $code)->firstOrFail();

        if ($check->evaluation !== CheckEvaluation::Manual) {
            throw new DomainRuleException('Checklist ini dihitung otomatis oleh sistem.', 'AUTO_CHECK', 422);
        }

        $check->forceFill(['status' => CheckStatus::from($status), 'note' => $note, 'checked_by' => $officer->getKey(), 'checked_at' => now()])->save();
        $this->history->action($check, AuditAction::Verified, ['check' => $code, 'status' => $status], $note);

        return $check;
    }

    /**
     * @param  array{fields?: array<int, string>, documents?: array<int, string>}  $targets
     */
    public function requestRevision(ReRegistration $reRegistration, array $targets, string $note, User $officer): ReRegistration
    {
        return DB::transaction(function () use ($reRegistration, $targets, $note, $officer): ReRegistration {
            $reRegistration = ReRegistration::query()->whereKey($reRegistration->getKey())->lockForUpdate()->firstOrFail();
            $from = $reRegistration->status;
            StateTransition::assert($from, ReRegistrationStatus::RevisionRequested, 'daftar ulang');
            $due = $reRegistration->effectiveDeadline()->isPast() ? now()->addHours($this->settings->int('pmb.revision_window_hours')) : null;

            $reRegistration->forceFill([
                'status' => ReRegistrationStatus::RevisionRequested,
                'revision_targets' => ['fields' => array_values((array) ($targets['fields'] ?? [])), 'documents' => array_values((array) ($targets['documents'] ?? []))],
                'revision_due_at' => $due,
                'extended_until' => $due && $due->gt($reRegistration->effectiveDeadline()) ? $due : $reRegistration->extended_until,
                'lock_version' => $reRegistration->lock_version + 1,
            ])->save();

            foreach (ApplicationDocument::query()->with('documentType')->where('application_id', $reRegistration->application_id)->where('stage', DocumentStage::ReRegistration->value)->get() as $document) {
                $targeted = in_array($document->getKey(), (array) ($targets['documents'] ?? []), true) || in_array($document->documentType?->code, (array) ($targets['documents'] ?? []), true);

                if ($targeted && in_array($document->status, [DocumentStatus::Uploaded, DocumentStatus::InReview, DocumentStatus::Approved], true)) {
                    $document->forceFill(['status' => DocumentStatus::RevisionRequested, 'locked_at' => null])->save();
                }
            }

            app(VerificationService::class)->addNote($reRegistration->application, 'applicant', $note, $officer, 're_registration');
            $this->history->record($reRegistration->application, Lifecycle::ReRegistration, $from, ReRegistrationStatus::RevisionRequested, Actor::user($officer), null, $note);
            $this->outbox->notifyApplicant($reRegistration->application->account, 'pmb.re_registration.revision_requested', [], aggregate: $reRegistration);

            return $reRegistration->refresh();
        });
    }

    /**
     * Tahap 11: persetujuan final (BR-33) oleh petugas yang BUKAN verifikator
     * bukti bayar manual biaya awal aplikasi ini (08 §4); konversi dibuat lewat
     * outbox (11 §A.18.6).
     */
    public function approve(ReRegistration $reRegistration, int $lockVersion, User $officer): StudentConversion
    {
        $checks = $this->evaluateChecks($reRegistration);
        $unmet = array_keys(array_filter($checks, fn ($c) => ! in_array($c['status'], ['passed', 'not_applicable'], true)));

        if ($unmet !== []) {
            throw new InvalidTransitionException('Checklist daftar ulang belum lengkap.', $unmet);
        }

        $proofReviewers = ManualPaymentProof::query()
            ->whereHas('transaction.invoice', fn ($q) => $q->where('application_id', $reRegistration->application_id)->where('type', InvoiceType::InitialTuition->value))
            ->pluck('reviewed_by')->filter()->map(fn ($id) => (string) $id)->all();

        if (in_array((string) $officer->getKey(), $proofReviewers, true)) {
            abort(403, 'Verifikator bukti bayar biaya awal tidak boleh menyetujui daftar ulang yang sama (pemisahan tugas).');
        }

        return DB::transaction(function () use ($reRegistration, $lockVersion, $officer): StudentConversion {
            $reRegistration = $this->lock($reRegistration, $lockVersion);
            $from = $reRegistration->status;
            StateTransition::assert($from, ReRegistrationStatus::Approved, 'daftar ulang');

            $reRegistration->forceFill([
                'status' => ReRegistrationStatus::Approved,
                'approved_at' => now(),
                'approved_by' => $officer->getKey(),
                'lock_version' => $reRegistration->lock_version + 1,
            ])->save();

            $application = $reRegistration->application;
            $this->documents->lockStage($application, DocumentStage::ReRegistration);
            $this->history->record($application, Lifecycle::ReRegistration, $from, ReRegistrationStatus::Approved, Actor::user($officer));

            $conversion = StudentConversion::query()->firstOrCreate(
                ['application_id' => $application->getKey()],
                ['university_id' => $application->university_id, 're_registration_id' => $reRegistration->getKey(), 'status' => ConversionStatus::Pending, 'steps_completed' => [], 'attempts' => 0, 'next_attempt_at' => now()],
            );

            $this->history->record($application, Lifecycle::Conversion, null, ConversionStatus::Pending, Actor::user($officer));
            $this->outbox->record(OutboxRecorder::CONVERSION_REQUESTED, $conversion, ['conversion_id' => $conversion->getKey()]);

            return $conversion;
        });
    }

    /** Perpanjangan individual (BR-34): Admin PMB, alasan wajib, maks N hari dari batas awal. */
    public function extend(ReRegistration $reRegistration, string $until, string $reason, User $admin): ReRegistration
    {
        $target = CarbonImmutable::parse($until);
        $max = CarbonImmutable::parse($reRegistration->deadline_at)->addDays($this->settings->int('pmb.re_registration_extension_max_days'));

        if ($target->gt($max) || $target->lte(CarbonImmutable::parse($reRegistration->deadline_at))) {
            throw ValidationException::withMessages(['extended_until' => 'Perpanjangan harus setelah batas awal dan maksimal '.$max->toDateTimeString().'.']);
        }

        if (! $reRegistration->status->isActive() || $reRegistration->status === ReRegistrationStatus::Approved) {
            throw new InvalidTransitionException('Daftar ulang tidak dapat diperpanjang pada status ini.');
        }

        $reRegistration->forceFill(['extended_until' => $target, 'extension_reason' => mb_substr($reason, 0, 255), 'extended_by' => $admin->getKey()])->save();
        $this->history->change($reRegistration, ['extended_until' => null], ['extended_until' => $target->toIso8601String()], $reason);
        $this->outbox->notifyApplicant($reRegistration->application->account, 'pmb.re_registration.deadline_reminder', ['deadline' => $target->toDateTimeString()], aggregate: $reRegistration);

        return $reRegistration->refresh();
    }

    /**
     * Perpanjangan massal (mis. gangguan sistem) → approval `pmb_bulk_extension`.
     *
     * @param  array<string, mixed>  $filter
     */
    public function requestBulkExtension(array $filter, string $until, string $reason, User $admin): ChangeRequest
    {
        $ids = $this->bulkTargets($filter)->pluck('id')->all();

        if ($ids === []) {
            throw ValidationException::withMessages(['filter' => 'Tidak ada daftar ulang aktif yang cocok.']);
        }

        return DB::transaction(function () use ($ids, $until, $reason, $admin, $filter): ChangeRequest {
            $change = ChangeRequest::query()->create([
                'type' => ChangeRequestType::BulkExtension,
                'payload' => ['re_registration_ids' => $ids, 'extended_until' => CarbonImmutable::parse($until)->toIso8601String(), 'filter' => $filter],
                'reason' => $reason,
                'status' => ChangeRequestStatus::PendingApproval,
                'requested_by' => $admin->getKey(),
            ]);
            $approval = $this->approvals->submit($change, PmbApprovalService::BULK_EXTENSION, $admin, $reason);
            $change->forceFill(['approval_request_id' => $approval->getKey()])->save();

            return $change;
        });
    }

    public function applyBulkExtension(ChangeRequest $change): void
    {
        $until = CarbonImmutable::parse($change->payload['extended_until']);

        foreach (ReRegistration::query()->whereIn('id', (array) $change->payload['re_registration_ids'])->get() as $reRegistration) {
            if ($reRegistration->status->isActive() && $reRegistration->status !== ReRegistrationStatus::Approved) {
                $reRegistration->forceFill(['extended_until' => $until, 'extension_reason' => 'Perpanjangan massal: '.mb_substr($change->reason, 0, 200), 'extended_by' => $change->requested_by])->save();
                $this->outbox->notifyApplicant($reRegistration->application->account, 'pmb.re_registration.deadline_reminder', ['deadline' => $until->toDateTimeString()], aggregate: $reRegistration);
            }
        }

        $change->forceFill(['status' => ChangeRequestStatus::Applied, 'applied_at' => now()])->save();
    }

    /**
     * Job harian (K.5, BR-35): lewat batas tanpa menyelesaikan → expired;
     * kursi dibebaskan; pembayaran sebagian → draft refund untuk ditinjau
     * keuangan. Pembayaran pending yang dibuat sebelum batas ditahan dulu.
     */
    public function expireOverdue(): int
    {
        $count = 0;
        $statuses = array_map(fn ($s) => $s->value, array_filter(ReRegistrationStatus::cases(), fn ($s) => $s->canExpire()));

        ReRegistration::query()->whereIn('status', $statuses)
            ->where(fn ($q) => $q->where(fn ($a) => $a->whereNull('extended_until')->where('deadline_at', '<', now()))->orWhere('extended_until', '<', now()))
            ->get()
            ->each(function (ReRegistration $reRegistration) use (&$count): void {
                $pendingBeforeDeadline = PmbInvoice::query()->where('application_id', $reRegistration->application_id)->where('type', InvoiceType::InitialTuition->value)
                    ->whereHas('transactions', fn ($q) => $q->where('status', TransactionStatus::Pending->value)->where('created_at', '<', $reRegistration->effectiveDeadline()))
                    ->exists();

                if ($pendingBeforeDeadline && $reRegistration->effectiveDeadline()->gt(now()->subDays(2))) {
                    return;
                }

                DB::transaction(function () use ($reRegistration, &$count): void {
                    $reRegistration = ReRegistration::query()->whereKey($reRegistration->getKey())->lockForUpdate()->first();

                    if ($reRegistration === null || ! $reRegistration->status->canExpire()) {
                        return;
                    }

                    $from = $reRegistration->status;
                    $reRegistration->forceFill(['status' => ReRegistrationStatus::Expired, 'expired_at' => now()])->save();
                    $this->history->record($reRegistration->application, Lifecycle::ReRegistration, $from, ReRegistrationStatus::Expired, Actor::system(), 'DEADLINE_PASSED');
                    $this->decisions->releaseSeatFor($reRegistration->decision);
                    $this->queuePartialRefunds($reRegistration);
                    $this->outbox->notifyApplicant($reRegistration->application->account, 'pmb.re_registration.expired', [], aggregate: $reRegistration);
                    $count++;
                });
            });

        return $count;
    }

    /**
     * Pemulihan terbatas (SM-10): declined → awaiting_confirmation dalam 24 jam
     * (Admin PMB); expired → in_progress bila pembayaran sah sebelum batas
     * (LATE_CALLBACK, perlu juga izin rekonsiliasi). Kursi harus masih tersedia.
     */
    public function restore(ReRegistration $reRegistration, string $reasonCode, string $note, User $admin): ReRegistration
    {
        return DB::transaction(function () use ($reRegistration, $reasonCode, $note, $admin): ReRegistration {
            $reRegistration = ReRegistration::query()->whereKey($reRegistration->getKey())->lockForUpdate()->firstOrFail();
            $from = $reRegistration->status;

            if ($from === ReRegistrationStatus::Declined) {
                if ($reRegistration->declined_at === null || $reRegistration->declined_at->lt(now()->subDay())) {
                    throw new DomainRuleException('Penolakan tawaran hanya dapat dipulihkan dalam 24 jam.', 'RESTORE_WINDOW_PASSED', 422);
                }

                $target = ReRegistrationStatus::AwaitingConfirmation;
            } elseif ($from === ReRegistrationStatus::Expired && $reasonCode === 'LATE_CALLBACK') {
                if (! $admin->hasPermissionTo('pmb_payments.finalize')) {
                    abort(403, 'Pemulihan LATE_CALLBACK memerlukan konfirmasi petugas keuangan.');
                }

                $paidInTime = PmbInvoice::query()->where('application_id', $reRegistration->application_id)->where('type', InvoiceType::InitialTuition->value)
                    ->whereHas('transactions', fn ($q) => $q->where('status', TransactionStatus::Success->value)->where('paid_at', '<=', $reRegistration->effectiveDeadline()))->exists();

                if (! $paidInTime) {
                    throw new DomainRuleException('Tidak ada pembayaran sah sebelum batas daftar ulang.', 'NOT_ELIGIBLE', 422);
                }

                $target = $reRegistration->confirmed_at ? ReRegistrationStatus::InProgress : ReRegistrationStatus::AwaitingConfirmation;
            } else {
                throw new InvalidTransitionException('Daftar ulang ini tidak dapat dipulihkan.');
            }

            // Kursi harus masih tersedia (409 bila sudah dipakai promosi cadangan).
            $this->publication->takeSeat((string) $reRegistration->decision->quota_id);

            $reRegistration->forceFill([
                'status' => $target,
                'declined_at' => null,
                'expired_at' => null,
                'extended_until' => now()->addDays(2)->max($reRegistration->effectiveDeadline()),
                'extension_reason' => 'Pemulihan: '.$reasonCode,
                'extended_by' => $admin->getKey(),
            ])->save();
            $this->history->record($reRegistration->application, Lifecycle::ReRegistration, $from, $target, Actor::user($admin), $reasonCode, $note);

            return $reRegistration->refresh();
        });
    }

    /** Pengingat H-3, H-1, 6 jam (K.4) bagi yang belum submitted — dideduplikasi per jendela. */
    public function sendDeadlineReminders(): int
    {
        $count = 0;

        foreach ([['H-3', 72], ['H-1', 24], ['6J', 6]] as [$label, $hours]) {
            ReRegistration::query()->with('application.account')
                ->whereIn('status', ['awaiting_confirmation', 'confirmed', 'in_progress', 'revision_requested'])
                ->get()
                ->filter(function (ReRegistration $r) use ($hours) {
                    $remaining = now()->diffInHours($r->effectiveDeadline(), false);

                    return $remaining > 0 && $remaining <= $hours && $remaining > ($hours === 72 ? 24 : ($hours === 24 ? 6 : 0));
                })
                ->each(function (ReRegistration $r) use ($label, &$count): void {
                    $key = 'pmb:rereg-reminder:'.$r->getKey().':'.$label;

                    if (Cache::add($key, true, now()->addDays(4))) {
                        $this->outbox->notifyApplicant($r->application->account, 'pmb.re_registration.deadline_reminder', ['deadline' => $r->effectiveDeadline()->toDateTimeString()], aggregate: $r);
                        $count++;
                    }
                });
        }

        return $count;
    }

    // ------------------------------------------------------------------ Helper

    public function ensureChecks(ReRegistration $reRegistration): void
    {
        $application = $reRegistration->application;
        $needsHealth = DocumentRequirement::query()->where('wave_path_id', $application->wave_path_id)->where('stage', 're_registration')
            ->whereHas('documentType', fn ($q) => $q->where('code', 'HEALTH_CERT'))->exists();
        $needsOriginal = DocumentRequirement::query()->where('wave_path_id', $application->wave_path_id)
            ->whereHas('documentType', fn ($q) => $q->where('requires_original_check', true))->exists();

        foreach (self::CHECKS as $code => $evaluation) {
            $applicable = match ($code) {
                'HEALTH_CLEARED' => $needsHealth,
                'ORIGINAL_DOCUMENTS_CHECKED' => $needsOriginal,
                default => true,
            };

            ReRegistrationCheck::query()->firstOrCreate(
                ['re_registration_id' => $reRegistration->getKey(), 'check_code' => $code],
                ['university_id' => $reRegistration->university_id, 'evaluation' => $evaluation, 'status' => $applicable ? CheckStatus::Pending : CheckStatus::NotApplicable],
            );
        }
    }

    /** @return array{0: CheckStatus, 1: ?string} */
    private function pddiktiComplete(?ApplicantProfile $profile): array
    {
        if ($profile === null) {
            return [CheckStatus::Failed, 'Profil belum diisi'];
        }

        $missing = [];

        foreach (['religion', 'mother_name', 'nationality', 'address_line', 'city', 'province'] as $field) {
            if (empty($profile->{$field})) {
                $missing[] = $field;
            }
        }

        if (($profile->nationality ?? 'ID') === 'ID' && empty($profile->nik)) {
            $missing[] = 'nik';
        }

        foreach (self::PDDIKTI_EXTRA_FIELDS as $field) {
            if (! array_key_exists($field, (array) ($profile->pddikti_extra ?? []))) {
                $missing[] = $field;
            }
        }

        return $missing === [] ? [CheckStatus::Passed, null] : [CheckStatus::Pending, 'Belum lengkap: '.implode(', ', $missing)];
    }

    /** @return array{0: CheckStatus, 1: ?string} */
    private function guardiansComplete(Application $application): array
    {
        $parents = $application->account->guardians->filter(fn ($g) => in_array($g->relationship, ['father', 'mother', 'guardian'], true) && $g->full_name);

        return $parents->isNotEmpty() ? [CheckStatus::Passed, null] : [CheckStatus::Pending, 'Data ayah/ibu/wali belum diisi'];
    }

    /** @return array{0: CheckStatus, 1: ?string} */
    private function finalDocuments(Application $application, bool $requireApproved): array
    {
        $context = app(ApplicationService::class)->ruleContext($application);
        $unmet = $this->documents->unmetMandatory($application, DocumentStage::ReRegistration, $requireApproved, $context);

        return $unmet === [] ? [CheckStatus::Passed, null] : [CheckStatus::Pending, 'Menunggu: '.implode(', ', array_column($unmet, 'document_type'))];
    }

    /** @return array{0: CheckStatus, 1: ?string} */
    private function healthCleared(Application $application): array
    {
        $doc = ApplicationDocument::query()->where('application_id', $application->getKey())->where('stage', 're_registration')
            ->whereHas('documentType', fn ($q) => $q->where('code', 'HEALTH_CERT'))->first();

        if ($doc === null) {
            return [CheckStatus::NotApplicable, null];
        }

        return $doc->status === DocumentStatus::Approved ? [CheckStatus::Passed, null] : [CheckStatus::Pending, 'Surat keterangan sehat belum disetujui'];
    }

    /** @return array{0: CheckStatus, 1: ?string} */
    private function paymentMinimum(Application $application): array
    {
        $invoice = PmbInvoice::query()->where('application_id', $application->getKey())->where('type', InvoiceType::InitialTuition->value)
            ->whereNotIn('status', [PmbInvoiceStatus::Cancelled->value])->latest()->first();

        if ($invoice === null) {
            return [CheckStatus::Pending, 'Tagihan biaya awal belum dibuat'];
        }

        $minimum = $this->settings->get('pmb.enrollment_min_payment') === 'full' ? (string) $invoice->amount_net : (string) ($invoice->minimum_for_enrollment ?? $invoice->amount_net);

        return Money::compare((string) $invoice->amount_paid, $minimum) >= 0
            ? [CheckStatus::Passed, null]
            : [CheckStatus::Pending, 'Dibayar '.Money::format((string) $invoice->amount_paid).' dari minimum '.Money::format($minimum)];
    }

    private function markInProgress(ReRegistration $reRegistration, Actor $actor): void
    {
        if ($reRegistration->status === ReRegistrationStatus::Confirmed) {
            $reRegistration->forceFill(['status' => ReRegistrationStatus::InProgress])->save();
            $this->history->record($reRegistration->application, Lifecycle::ReRegistration, ReRegistrationStatus::Confirmed, ReRegistrationStatus::InProgress, $actor);
        }
    }

    private function assertEditable(ReRegistration $reRegistration, string $section): void
    {
        $editable = in_array($reRegistration->status, [ReRegistrationStatus::Confirmed, ReRegistrationStatus::InProgress], true)
            || ($reRegistration->status === ReRegistrationStatus::RevisionRequested);

        if (! $editable || $reRegistration->effectiveDeadline()->isPast() && $reRegistration->status !== ReRegistrationStatus::RevisionRequested) {
            throw new DomainRuleException('Data daftar ulang tidak dapat diubah pada status ini.', 'RE_REGISTRATION_LOCKED', 423);
        }
    }

    private function lock(ReRegistration $reRegistration, int $lockVersion): ReRegistration
    {
        $locked = ReRegistration::query()->whereKey($reRegistration->getKey())->lockForUpdate()->firstOrFail();

        if ($locked->lock_version !== $lockVersion) {
            throw new DomainRuleException('Data telah diubah di tempat lain. Muat ulang.', 'VERSION_CONFLICT', 409, ['current_version' => $locked->lock_version]);
        }

        return $locked;
    }

    /**
     * A-17: menerima satu tawaran → tawaran lain di periode sama ditarik
     * (keputusan versi baru `withdrawn`), kursinya dibebaskan; aplikasi
     * lain yang belum berkeputusan ditarik.
     *
     * @return array<int, string>
     */
    private function withdrawOtherOffers(Application $application, Actor $actor): array
    {
        $withdrawn = [];
        $others = Application::query()->where('applicant_account_id', $application->applicant_account_id)->where('period_id', $application->period_id)
            ->whereKeyNot($application->getKey())->active()->get();

        foreach ($others as $other) {
            $decision = AdmissionDecision::query()->where('application_id', $other->getKey())->where('is_current', true)->lockForUpdate()->first();

            if ($decision !== null && $decision->publication_status === PublicationStatus::Published) {
                if (in_array($decision->decision, [Decision::Accepted, Decision::Waitlisted], true)) {
                    $decision->forceFill(['is_current' => false, 'publication_status' => PublicationStatus::Superseded])->save();
                    AdmissionDecision::query()->create([
                        'university_id' => $other->university_id,
                        'application_id' => $other->getKey(),
                        'decision_version' => $decision->decision_version + 1,
                        'is_current' => true,
                        'decision' => Decision::Withdrawn,
                        'source' => DecisionSource::OfferWithdrawal,
                        'selection_run_id' => $decision->selection_run_id,
                        'publication_status' => PublicationStatus::Published,
                        'published_at' => now(),
                        'correction_note' => 'Menerima tawaran lain ('.$application->registration_number.')',
                        'previous_decision_id' => $decision->getKey(),
                    ]);

                    $otherReRegistration = ReRegistration::query()->where('application_id', $other->getKey())->lockForUpdate()->first();

                    if ($otherReRegistration !== null && $otherReRegistration->status->isActive()) {
                        $from = $otherReRegistration->status;
                        $otherReRegistration->forceFill(['status' => ReRegistrationStatus::Declined, 'declined_at' => now(), 'decline_reason' => 'Menerima tawaran lain'])->save();
                        $this->history->record($other, Lifecycle::ReRegistration, $from, ReRegistrationStatus::Declined, $actor, 'OTHER_OFFER_ACCEPTED');
                    }

                    if ($decision->decision === Decision::Accepted && (! $otherReRegistration || ! in_array($otherReRegistration->status, [ReRegistrationStatus::Expired, ReRegistrationStatus::Cancelled], true))) {
                        $this->decisions->releaseSeatFor($decision);
                    }

                    $withdrawn[] = (string) $other->getKey();
                }

                continue;
            }

            if (in_array($other->form_status, [FormStatus::Draft], true)) {
                app(ApplicationService::class)->discardDraft($other, $actor, 'OTHER_OFFER_ACCEPTED');
                $withdrawn[] = (string) $other->getKey();
            } elseif ($other->form_status->canTransitionTo(FormStatus::Withdrawn)) {
                app(ApplicationService::class)->withdraw($other, 'Menerima tawaran lain', $actor);
                $withdrawn[] = (string) $other->getKey();
            }
        }

        return $withdrawn;
    }

    private function queuePartialRefunds(ReRegistration $reRegistration): void
    {
        $invoice = PmbInvoice::query()->where('application_id', $reRegistration->application_id)->where('type', InvoiceType::InitialTuition->value)->first();

        if ($invoice === null || Money::isZero((string) $invoice->amount_paid)) {
            return;
        }

        foreach ($invoice->transactions()->where('status', TransactionStatus::Success->value)->get() as $transaction) {
            if (! Refund::query()->where('transaction_id', $transaction->getKey())->exists()) {
                Refund::query()->create([
                    'transaction_id' => $transaction->getKey(),
                    'amount' => (string) $transaction->amount,
                    'reason_code' => 'POLICY',
                    'reason_note' => 'Daftar ulang kedaluwarsa — tinjau sesuai kebijakan refund (BR-35).',
                    'beneficiary_bank' => '-',
                    'beneficiary_account' => '-',
                    'beneficiary_name' => $reRegistration->application->account->name,
                    'status' => 'draft',
                ]);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $filter
     * @return Builder<ReRegistration>
     */
    private function bulkTargets(array $filter): Builder
    {
        return ReRegistration::query()->whereIn('status', ['awaiting_confirmation', 'confirmed', 'in_progress', 'revision_requested'])
            ->when($filter['wave_id'] ?? null, fn ($q, $v) => $q->whereHas('application', fn ($a) => $a->where('wave_id', $v)))
            ->when($filter['study_program_id'] ?? null, fn ($q, $v) => $q->whereHas('decision', fn ($d) => $d->where('study_program_id', $v)))
            ->when($filter['period_id'] ?? null, fn ($q, $v) => $q->whereHas('application', fn ($a) => $a->where('period_id', $v)));
    }
}
