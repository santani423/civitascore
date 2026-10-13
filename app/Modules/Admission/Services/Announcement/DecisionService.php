<?php

namespace Modules\Admission\Services\Announcement;

use App\Models\User;
use App\Support\Http\Exceptions\ConflictException;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Admission\Enums\ChangeRequestStatus;
use Modules\Admission\Enums\ChangeRequestType;
use Modules\Admission\Enums\Decision;
use Modules\Admission\Enums\DecisionSource;
use Modules\Admission\Enums\Lifecycle;
use Modules\Admission\Enums\PublicationStatus;
use Modules\Admission\Enums\ReRegistrationStatus;
use Modules\Admission\Exceptions\DomainRuleException;
use Modules\Admission\Models\AdmissionDecision;
use Modules\Admission\Models\Application;
use Modules\Admission\Models\ChangeRequest;
use Modules\Admission\Models\Quota;
use Modules\Admission\Models\ReRegistration;
use Modules\Admission\Models\SelectionRun;
use Modules\Admission\Outbox\OutboxRecorder;
use Modules\Admission\Services\Approval\PmbApprovalService;
use Modules\Admission\Services\Selection\SelectionRunService;
use Modules\Admission\Support\Actor;
use Modules\Admission\Support\HistoryRecorder;
use Modules\Admission\Support\PmbSettings;
use Modules\AuditLog\Enums\AuditAction;

/**
 * Keputusan setelah publikasi: promosi cadangan (J.4, BR-32), koreksi hasil
 * berversi (J.5, BR-31), pembatalan penerimaan oleh kampus (K.5).
 */
class DecisionService
{
    public function __construct(
        private readonly PublicationService $publication,
        private readonly HistoryRecorder $history,
        private readonly OutboxRecorder $outbox,
        private readonly PmbApprovalService $approvals,
        private readonly PmbSettings $settings,
    ) {}

    /**
     * Promosikan cadangan berikutnya (J.4): rank terendah yang masih
     * `waitlisted` pada prodi itu, dalam transaksi + lock kuota (KD-11);
     * melompati urutan tidak diizinkan; dua admin bersamaan → satu berhasil.
     *
     * @return array<int, AdmissionDecision>
     */
    public function promoteWaitlist(Quota $quota, int $count, ?string $deadline, ?User $actor): array
    {
        $promoted = [];

        for ($i = 0; $i < max(1, $count); $i++) {
            $decision = DB::transaction(function () use ($quota, $deadline, $actor): ?AdmissionDecision {
                $quota = Quota::query()->whereKey($quota->getKey())->lockForUpdate()->firstOrFail();

                if ($quota->accepted_count >= $quota->capacity) {
                    throw new ConflictException('Tidak ada kursi kosong pada kuota ini.', 'QUOTA_FULL');
                }

                $candidate = $this->nextWaitlisted($quota);

                if ($candidate === null) {
                    return null;
                }

                $candidate->forceFill(['is_current' => false])->save();
                $application = $candidate->application;
                $deadlineAt = $deadline ? CarbonImmutable::parse($deadline) : now()->addDays(max(1, $this->settings->int('pmb.waitlist_deadline_days')))->endOfDay();

                $decision = AdmissionDecision::query()->create([
                    'university_id' => $application->university_id,
                    'application_id' => $application->getKey(),
                    'decision_version' => $candidate->decision_version + 1,
                    'is_current' => true,
                    'decision' => Decision::Accepted,
                    'source' => DecisionSource::WaitlistPromotion,
                    'selection_run_id' => $candidate->selection_run_id,
                    'run_result_id' => $candidate->run_result_id,
                    'quota_id' => $quota->getKey(),
                    'study_program_id' => $quota->study_program_id,
                    'study_system_id' => $quota->study_system_id,
                    'publication_status' => PublicationStatus::Unpublished,
                    'previous_decision_id' => $candidate->getKey(),
                    'created_by' => $actor?->getKey(),
                ]);

                $this->publication->publishDecision($decision, null, false);
                $reRegistration = ReRegistration::query()->where('application_id', $application->getKey())->first();
                $reRegistration?->forceFill(['deadline_at' => $deadlineAt])->save();

                $this->outbox->notifyApplicant($application->account, 'pmb.waitlist.promoted', ['deadline' => $deadlineAt->toDateTimeString()], aggregate: $decision);
                $this->history->action($decision, AuditAction::Updated, ['promoted_from_rank' => $candidate->waitlist_rank, 'quota_id' => $quota->getKey()]);

                return $decision->refresh();
            });

            if ($decision === null) {
                if ($promoted === []) {
                    throw new DomainRuleException('Daftar cadangan untuk prodi ini sudah habis.', 'WAITLIST_EMPTY', 404);
                }

                break;
            }

            $promoted[] = $decision;
        }

        return $promoted;
    }

    /**
     * Kursi kosong (tolak/kedaluwarsa/batal) → kurangi accepted_count; promosi
     * otomatis hanya bila tenant mengaktifkan (BR-32 Phase 2).
     */
    public function releaseSeatFor(AdmissionDecision $decision): void
    {
        if ($decision->decision !== Decision::Accepted || $decision->quota_id === null) {
            return;
        }

        $this->publication->releaseSeat((string) $decision->quota_id);
        $this->outbox->record(OutboxRecorder::SEAT_RELEASED, $decision, ['quota_id' => $decision->quota_id]);
    }

    public function autoPromote(string $quotaId): void
    {
        if (! $this->settings->bool('pmb.auto_promote_waitlist')) {
            return;
        }

        $quota = Quota::query()->find($quotaId);

        if ($quota !== null && $quota->accepted_count < $quota->capacity) {
            try {
                $this->promoteWaitlist($quota, 1, null, null);
            } catch (DomainRuleException|ConflictException) {
                // Cadangan habis / kursi sudah terisi: tidak ada tindakan.
            }
        }
    }

    /**
     * Keputusan beasiswa jalur masuk (FR-SPC-06): terpisah dari keputusan
     * penerimaan; potongan diterapkan pada tagihan biaya awal & diteruskan ke
     * modul Scholarship saat konversi.
     */
    public function setScholarshipAward(AdmissionDecision $decision, ?string $scholarshipId, float $percent, string $note, User $actor): AdmissionDecision
    {
        if ($percent < 0 || $percent > 100) {
            throw ValidationException::withMessages(['percent' => 'Persentase 0–100.']);
        }

        if ($scholarshipId !== null && ! \Modules\Scholarship\Models\Scholarship::query()->whereKey($scholarshipId)->exists()) {
            throw ValidationException::withMessages(['scholarship_id' => 'Program beasiswa tidak ditemukan.']);
        }

        $old = $decision->scholarship_award;
        $award = $percent > 0 ? ['scholarship_id' => $scholarshipId, 'percent' => $percent, 'note' => $note, 'awarded_by' => $actor->getKey(), 'awarded_at' => now()->toIso8601String()] : null;
        $decision->forceFill(['scholarship_award' => $award])->save();
        $this->history->change($decision, ['scholarship_award' => $old], ['scholarship_award' => $award], $note);

        return $decision->refresh();
    }

    /**
     * Ringkasan kursi per kuota untuk halaman promosi (ADM-62).
     *
     * @return array<int, array<string, mixed>>
     */
    public function seatOverview(string $periodId): array
    {
        return Quota::query()->with(['studyProgram', 'studySystem', 'admissionPath'])->where('period_id', $periodId)->where('is_offered', true)->get()
            ->map(fn (Quota $q) => [
                'quota_id' => $q->getKey(),
                'program' => $q->studyProgram?->name,
                'study_system' => $q->studySystem?->name,
                'path' => $q->admissionPath?->name,
                'capacity' => $q->capacity,
                'accepted_active' => $q->accepted_count,
                'free_seats' => max(0, $q->capacity - $q->accepted_count),
                'waitlist_remaining' => $this->waitlistQuery($q)->count(),
                'next_candidate' => optional($this->nextWaitlisted($q))->only(['application_id', 'waitlist_rank']),
            ])->all();
    }

    /**
     * Ajukan koreksi hasil (J.5): `proposed` = {application_id: {decision, quota_id?}}
     * diterapkan langsung bila disetujui; tanpa `proposed` → run koreksi.
     *
     * @param  array<int, string>  $applicationIds
     * @param  array<string, array{decision: string, quota_id?: string|null}>  $proposed
     */
    public function requestCorrection(array $applicationIds, string $reason, ?string $evidenceFileId, array $proposed, User $actor): ChangeRequest
    {
        $applications = Application::query()->whereIn('id', $applicationIds)->get();

        if ($applications->isEmpty() || $applications->contains(fn ($a) => ! $a->currentDecision()->published()->exists())) {
            throw ValidationException::withMessages(['application_ids' => 'Koreksi hanya untuk keputusan yang sudah terbit.']);
        }

        foreach ($proposed as $applicationId => $item) {
            Decision::from($item['decision']);

            if ($item['decision'] === 'accepted' && empty($item['quota_id'])) {
                throw ValidationException::withMessages(["proposed.{$applicationId}.quota_id" => 'Prodi tujuan wajib untuk keputusan diterima.']);
            }
        }

        return DB::transaction(function () use ($applicationIds, $reason, $evidenceFileId, $proposed, $actor): ChangeRequest {
            $change = ChangeRequest::query()->create([
                'type' => ChangeRequestType::ResultCorrection,
                'payload' => ['application_ids' => array_values($applicationIds), 'proposed' => $proposed],
                'reason' => $reason,
                'evidence_file_id' => $evidenceFileId,
                'status' => ChangeRequestStatus::PendingApproval,
                'requested_by' => $actor->getKey(),
            ]);
            $approval = $this->approvals->submit($change, PmbApprovalService::RESULT_CORRECTION, $actor, $reason);
            $change->forceFill(['approval_request_id' => $approval->getKey()])->save();

            return $change;
        });
    }

    /**
     * Disetujui → keputusan versi baru (versi lama tetap, superseded);
     * kursi penuh untuk penerima tambahan → kapasitas ditambah sebagai bagian
     * koreksi yang disahkan (J.5 aturan), dicatat audit.
     */
    public function applyCorrection(ChangeRequest $change): void
    {
        $proposed = (array) ($change->payload['proposed'] ?? []);

        if ($proposed === []) {
            $this->startCorrectionRun($change);

            return;
        }

        DB::transaction(function () use ($change, $proposed): void {
            foreach ($proposed as $applicationId => $item) {
                $application = Application::query()->findOrFail($applicationId);
                $current = AdmissionDecision::query()->where('application_id', $applicationId)->where('is_current', true)->lockForUpdate()->firstOrFail();
                $target = Decision::from($item['decision']);
                $quota = ! empty($item['quota_id']) ? Quota::query()->whereKey($item['quota_id'])->lockForUpdate()->firstOrFail() : null;

                if ($target === Decision::Accepted && $quota !== null && ! ($current->decision === Decision::Accepted && $current->quota_id === $quota->getKey()) && $quota->accepted_count >= $quota->capacity) {
                    $old = $quota->capacity;
                    $quota->forceFill(['capacity' => $quota->accepted_count + 1])->save();
                    $this->history->change($quota, ['capacity' => $old], ['capacity' => $quota->capacity], 'Penambahan kapasitas oleh koreksi hasil '.$change->getKey());
                }

                $current->forceFill(['is_current' => false])->save();
                $decision = AdmissionDecision::query()->create([
                    'university_id' => $application->university_id,
                    'application_id' => $applicationId,
                    'decision_version' => $current->decision_version + 1,
                    'is_current' => true,
                    'decision' => $target,
                    'source' => DecisionSource::Correction,
                    'selection_run_id' => $current->selection_run_id,
                    'run_result_id' => $current->run_result_id,
                    'quota_id' => $target === Decision::Accepted ? $quota?->getKey() : null,
                    'study_program_id' => $target === Decision::Accepted ? $quota?->study_program_id : null,
                    'study_system_id' => $target === Decision::Accepted ? $quota?->study_system_id : null,
                    'waitlist_rank' => $target === Decision::Waitlisted ? $current->waitlist_rank : null,
                    'waitlist_entries' => $target === Decision::Waitlisted ? $current->waitlist_entries : null,
                    'publication_status' => PublicationStatus::Unpublished,
                    'correction_note' => $change->reason,
                    'previous_decision_id' => $current->getKey(),
                    'created_by' => $change->requested_by,
                ]);

                $this->publication->publishDecision($decision, null, true);
            }

            $change->forceFill(['status' => ChangeRequestStatus::Applied, 'applied_at' => now()])->save();
        });
    }

    /**
     * Pembatalan penerimaan oleh kampus sebelum `enrolled` (K.5): approval
     * `pmb_result_correction` → keputusan versi baru `rejected`, daftar ulang
     * `cancelled`, LoA dicabut. Setelah enrolled bukan wewenang PMB (BR-39).
     */
    public function requestCancellation(ReRegistration $reRegistration, string $reason, ?string $evidenceFileId, User $actor): ChangeRequest
    {
        if ($reRegistration->status === ReRegistrationStatus::Enrolled) {
            throw new DomainRuleException('Mahasiswa sudah terbentuk; pembatalan melalui proses status mahasiswa di Akademik (BR-39).', 'ALREADY_ENROLLED', 422);
        }

        if (! $reRegistration->status->isActive()) {
            throw new ConflictException('Daftar ulang sudah berakhir.', 'INVALID_TRANSITION');
        }

        return DB::transaction(function () use ($reRegistration, $reason, $evidenceFileId, $actor): ChangeRequest {
            $change = ChangeRequest::query()->create([
                'type' => ChangeRequestType::ReRegistrationCancellation,
                'subject_type' => 'ReRegistration',
                'subject_id' => $reRegistration->getKey(),
                'payload' => ['application_id' => $reRegistration->application_id],
                'reason' => $reason,
                'evidence_file_id' => $evidenceFileId,
                'status' => ChangeRequestStatus::PendingApproval,
                'requested_by' => $actor->getKey(),
            ]);
            $approval = $this->approvals->submit($change, PmbApprovalService::RESULT_CORRECTION, $actor, $reason);
            $change->forceFill(['approval_request_id' => $approval->getKey()])->save();

            return $change;
        });
    }

    public function applyCancellation(ChangeRequest $change): void
    {
        DB::transaction(function () use ($change): void {
            $reRegistration = ReRegistration::query()->whereKey($change->subject_id)->lockForUpdate()->firstOrFail();

            if ($reRegistration->status === ReRegistrationStatus::Enrolled) {
                $change->forceFill(['status' => ChangeRequestStatus::Failed, 'result_note' => 'Sudah enrolled sebelum persetujuan.'])->save();

                return;
            }

            $current = AdmissionDecision::query()->where('application_id', $reRegistration->application_id)->where('is_current', true)->lockForUpdate()->firstOrFail();
            $current->forceFill(['is_current' => false])->save();
            $decision = AdmissionDecision::query()->create([
                'university_id' => $current->university_id,
                'application_id' => $current->application_id,
                'decision_version' => $current->decision_version + 1,
                'is_current' => true,
                'decision' => Decision::Rejected,
                'source' => DecisionSource::Correction,
                'selection_run_id' => $current->selection_run_id,
                'publication_status' => PublicationStatus::Unpublished,
                'correction_note' => 'Penerimaan dibatalkan: '.$change->reason,
                'previous_decision_id' => $current->getKey(),
                'created_by' => $change->requested_by,
            ]);

            // publishDecision membatalkan daftar ulang, membebaskan kursi, dan mencabut LoA.
            $this->publication->publishDecision($decision, null, true);
            $this->outbox->notifyApplicant($reRegistration->application->account, 'pmb.re_registration.cancelled', [], aggregate: $reRegistration);
            $change->forceFill(['status' => ChangeRequestStatus::Applied, 'applied_at' => now()])->save();
        });
    }

    private function startCorrectionRun(ChangeRequest $change): void
    {
        $applicationIds = (array) ($change->payload['application_ids'] ?? []);
        $applications = Application::query()->whereIn('id', $applicationIds)->get();
        $requester = User::query()->findOrFail($change->requested_by);

        foreach ($applications->groupBy('wave_path_id') as $wavePathId => $group) {
            $run = app(SelectionRunService::class)->create($group->first()->wavePath, $requester, [], null, 'correction', $group->pluck('id')->map(fn ($id) => (string) $id)->all(), (string) $change->getKey());
            $this->history->action($run, AuditAction::Created, ['correction_request' => $change->getKey()], $change->reason);
        }

        $change->forceFill(['status' => ChangeRequestStatus::Applied, 'applied_at' => now(), 'result_note' => 'Run koreksi dibuat; hasilnya diajukan pengesahan sebelum terbit.'])->save();
    }

    private function nextWaitlisted(Quota $quota): ?AdmissionDecision
    {
        return $this->waitlistQuery($quota)->get()
            ->sortBy(fn (AdmissionDecision $d) => collect($d->waitlist_entries ?? [])->firstWhere('quota_id', $quota->getKey())['rank'] ?? PHP_INT_MAX)
            ->first();
    }

    /** @return \Illuminate\Database\Eloquent\Builder<AdmissionDecision> */
    private function waitlistQuery(Quota $quota): \Illuminate\Database\Eloquent\Builder
    {
        return AdmissionDecision::query()->with('application.account')
            ->where('decision', Decision::Waitlisted->value)->where('is_current', true)
            ->where('publication_status', PublicationStatus::Published->value)
            ->whereHas('application', fn ($q) => $q->where('period_id', $quota->period_id)->where('admission_path_id', $quota->admission_path_id)->whereNotIn('form_status', ['withdrawn']))
            ->where(fn ($q) => $q->where('quota_id', $quota->getKey())->orWhere('waitlist_entries', 'like', '%'.$quota->getKey().'%'));
    }
}
