<?php

namespace Modules\Admission\Services\Selection;

use App\Models\User;
use App\Support\Http\Exceptions\ConflictException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Admission\Enums\AttendanceStatus;
use Modules\Admission\Enums\ComponentSource;
use Modules\Admission\Enums\Decision;
use Modules\Admission\Enums\DecisionSource;
use Modules\Admission\Enums\Lifecycle;
use Modules\Admission\Enums\PublicationStatus;
use Modules\Admission\Enums\Recommendation;
use Modules\Admission\Enums\RunStatus;
use Modules\Admission\Enums\SchemeStatus;
use Modules\Admission\Enums\SelectionStatus;
use Modules\Admission\Enums\VerificationStatus;
use Modules\Admission\Exceptions\DomainRuleException;
use Modules\Admission\Exceptions\InvalidTransitionException;
use Modules\Admission\Jobs\ComputeSelectionRun;
use Modules\Admission\Models\AdmissionDecision;
use Modules\Admission\Models\AnnouncementBatch;
use Modules\Admission\Models\Application;
use Modules\Admission\Models\ExamParticipant;
use Modules\Admission\Models\ExamSession;
use Modules\Admission\Models\Quota;
use Modules\Admission\Models\SelectionRun;
use Modules\Admission\Models\SelectionRunResult;
use Modules\Admission\Models\SelectionScore;
use Modules\Admission\Models\WavePath;
use Modules\Admission\Outbox\OutboxRecorder;
use Modules\Admission\Selection\ComponentDefinition;
use Modules\Admission\Selection\SelectionAllocator;
use Modules\Admission\Selection\SelectionCalculator;
use Modules\Admission\Services\Approval\PmbApprovalService;
use Modules\Admission\Services\Configuration\SelectionSchemeService;
use Modules\Admission\Support\Actor;
use Modules\Admission\Support\HistoryRecorder;
use Modules\Admission\Support\PmbPermission;
use Modules\Admission\Support\PmbSettings;
use Modules\Admission\Support\StateTransition;
use Modules\AuditLog\Enums\AuditAction;
use Throwable;

/**
 * Proses seleksi (06 §H.6–H.7, SM-08, KD-04): run = snapshot immutable
 * atas skema terkunci & kuota saat hitung; review + override berlapis;
 * seri dibakukan panitia lewat run baru; pengesahan via approval engine.
 */
class SelectionRunService
{
    public function __construct(
        private readonly SelectionSchemeService $schemes,
        private readonly HistoryRecorder $history,
        private readonly OutboxRecorder $outbox,
        private readonly PmbApprovalService $approvals,
        private readonly PmbSettings $settings,
        private readonly ScoreService $scores,
    ) {}

    /**
     * 11 §A.18.5: skema locked; semua peserta eligible punya nilai komponen
     * wajib atau absent/disqualified; nilai sesi sudah dikunci; tidak ada run
     * pending_approval/approved yang belum terbit untuk wave path yang sama.
     *
     * @param  array<string, int>  $manualOrder  dari resolve-ties
     */
    public function create(WavePath $wavePath, User $actor, array $manualOrder = [], ?string $tieReason = null, string $runType = 'regular', ?array $scope = null, ?string $correctionRequestId = null): SelectionRun
    {
        $scheme = $wavePath->selectionScheme;

        if ($scheme === null || $scheme->status !== SchemeStatus::Locked) {
            throw new DomainRuleException('Skema seleksi gelombang-jalur belum dikunci.', 'SCHEME_NOT_LOCKED', 422);
        }

        if ($runType === 'regular') {
            $blocking = SelectionRun::query()->where('wave_path_id', $wavePath->getKey())
                ->whereIn('status', [RunStatus::PendingApproval->value, RunStatus::Approved->value])->exists();

            if ($blocking) {
                throw new ConflictException('Masih ada run yang menunggu pengesahan atau belum terbit untuk gelombang-jalur ini.', 'RUN_IN_PROGRESS');
            }

            $this->assertScoresReady($wavePath);
        }

        $run = DB::transaction(fn (): SelectionRun => SelectionRun::query()->create([
            'wave_path_id' => $wavePath->getKey(),
            'selection_scheme_id' => $scheme->getKey(),
            'run_type' => $runType,
            'scope_application_ids' => $scope,
            'status' => RunStatus::Draft,
            'summary' => $manualOrder !== [] ? ['manual_order' => $manualOrder, 'tie_resolution_reason' => $tieReason] : null,
            'correction_request_id' => $correctionRequestId,
            'created_by' => $actor->getKey(),
        ]));

        // Di luar transaksi afterCommit langsung mengeksekusi; di dalam transaksi menunggu commit.
        DB::afterCommit(fn () => ComputeSelectionRun::dispatch($run->getKey()));

        return $run->refresh();
    }

    /**
     * Menghitung snapshot (FR-SEL-03): skor, eligibility, alokasi BR-28,
     * ranking & tie-break, ringkasan, hash. Run lain yang computed/under_review
     * pada wave path sama → superseded.
     */
    public function compute(SelectionRun $run): SelectionRun
    {
        if ($run->status !== RunStatus::Draft) {
            return $run;
        }

        try {
            return DB::transaction(function () use ($run): SelectionRun {
                $run = SelectionRun::query()->whereKey($run->getKey())->lockForUpdate()->firstOrFail();
                $wavePath = $run->wavePath()->with('wave')->first();
                $scheme = $run->scheme()->with('components')->first();
                $components = $scheme->components->map(fn ($c) => ComponentDefinition::fromModel($c))->all();
                $isCorrection = $run->run_type === 'correction';

                $applications = Application::query()->with(['choices', 'scores'])
                    ->where('wave_path_id', $wavePath->getKey())
                    ->where('verification_status', VerificationStatus::Verified->value)
                    ->whereIn('selection_status', [SelectionStatus::Eligible->value, SelectionStatus::Scheduled->value, SelectionStatus::Assessed->value, SelectionStatus::Absent->value, SelectionStatus::Disqualified->value, SelectionStatus::Ranked->value])
                    ->whereNotIn('form_status', ['withdrawn', 'void'])
                    ->when($isCorrection, fn ($q) => $q->whereIn('id', (array) $run->scope_application_ids))
                    ->get();

                $quotaSnapshot = $this->quotaSnapshot($wavePath, $scheme->passing_score, $isCorrection ? (array) $run->scope_application_ids : []);
                $absences = $this->absences($wavePath, $scheme->components->pluck('id')->all());
                $sessionStats = $this->sessionStats($scheme->components->all(), $applications->pluck('id')->all());
                $calculator = new SelectionCalculator;
                $prepared = [];

                foreach ($applications as $application) {
                    $input = $this->scoreInput($application, $scheme->components->all(), $absences);
                    $evaluation = $calculator->evaluate($components, $input, (int) $scheme->rounding_scale, $application->selection_status === SelectionStatus::Disqualified, $sessionStats);
                    $prepared[] = [
                        'id' => (string) $application->getKey(),
                        'eligible' => $evaluation['eligible'],
                        'reason' => $evaluation['reason'],
                        'final' => $evaluation['final'],
                        'rounded' => $evaluation['rounded'],
                        'normalized' => array_map(fn ($c) => $c['normalized'], $evaluation['components']),
                        'components' => $evaluation['components'],
                        'verified_at' => $application->verified_at?->toIso8601String(),
                        'choices' => $application->choices->map(fn ($c) => ['choice_id' => (string) $c->getKey(), 'quota_id' => (string) $c->quota_id, 'priority' => $c->priority])->all(),
                    ];
                }

                $quotaInput = array_map(fn ($q) => ['remaining' => $q['remaining'], 'threshold' => $q['threshold'], 'waitlist_capacity' => $q['waitlist_capacity']], $quotaSnapshot);
                $manualOrder = (array) ($run->summary['manual_order'] ?? []);
                $useVerifiedAt = (bool) ($scheme->tie_break_rules['use_verified_at'] ?? $this->settings->bool('pmb.tie_break_use_verified_at'));
                $allocation = (new SelectionAllocator)->allocate($prepared, $quotaInput, $this->schemes->tieBreakComponents($scheme), $useVerifiedAt, $manualOrder, $scheme->allocation_method->value);
                $rows = [];

                foreach ($prepared as $item) {
                    $result = $allocation['results'][$item['id']];
                    $row = [
                        'university_id' => $run->university_id,
                        'selection_run_id' => $run->getKey(),
                        'application_id' => $item['id'],
                        'eligible' => $item['eligible'],
                        'ineligible_reason' => $item['eligible'] ? null : $item['reason'],
                        'component_scores' => $item['components'],
                        'final_score' => $item['final'],
                        'final_score_rounded' => $item['rounded'],
                        'rank_global' => $result['rank_global'],
                        'recommendation' => $result['recommendation'],
                        'fail_reason' => $result['fail_reason'],
                        'assigned_choice_id' => $result['assigned_choice_id'],
                        'assigned_quota_id' => $result['assigned_quota_id'],
                        'waitlist_entries' => $result['waitlist_entries'] ?: null,
                        'tie_break_values' => $result['tie_break_values'],
                        'tie_unresolved' => (bool) ($result['tie_unresolved'] ?? false),
                    ];
                    SelectionRunResult::query()->create($row);
                    $rows[] = $row;
                }

                $run->forceFill([
                    'status' => RunStatus::Computed,
                    'quota_snapshot' => $quotaSnapshot,
                    'summary' => [...(array) $run->summary, ...$this->summary($rows, $quotaSnapshot)],
                    'result_hash' => SelectionAllocator::hash($rows),
                    'has_unresolved_ties' => $allocation['has_unresolved_ties'],
                    'computed_at' => now(),
                ])->save();

                if (! $isCorrection) {
                    SelectionRun::query()->where('wave_path_id', $run->wave_path_id)->whereKeyNot($run->getKey())
                        ->whereIn('status', [RunStatus::Computed->value, RunStatus::UnderReview->value, RunStatus::Rejected->value, RunStatus::Failed->value])
                        ->update(['status' => RunStatus::Superseded->value, 'superseded_by_id' => $run->getKey()]);
                }

                $this->outbox->notifyPermission($run, PmbPermission::SELECTION_MANAGE, 'pmb.ops.alert', ['message' => 'Perhitungan seleksi selesai: '.count($rows).' aplikasi.']);

                return $run->refresh();
            });
        } catch (Throwable $e) {
            SelectionRun::query()->whereKey($run->getKey())->update(['status' => RunStatus::Failed->value, 'failure_reason' => mb_substr($e->getMessage(), 0, 2000)]);

            throw $e;
        }
    }

    public function startReview(SelectionRun $run, User $actor): SelectionRun
    {
        StateTransition::assert($run->status, RunStatus::UnderReview, 'run seleksi');
        $run->forceFill(['status' => RunStatus::UnderReview])->save();
        $this->history->action($run, AuditAction::Updated, ['status' => 'under_review']);

        return $run;
    }

    /**
     * Override individual sebelum pengajuan (FR-ANN-01): lapisan terpisah,
     * alasan wajib; kursi diterima tidak boleh melebihi snapshot kuota.
     */
    public function override(SelectionRunResult $result, string $recommendation, ?string $quotaId, string $reason, User $actor): SelectionRunResult
    {
        $run = $result->run;

        if ($run->status !== RunStatus::UnderReview) {
            throw new InvalidTransitionException('Override hanya selama run dalam peninjauan.');
        }

        $value = Recommendation::from($recommendation);

        if (trim($reason) === '') {
            throw ValidationException::withMessages(['reason' => 'Alasan override wajib diisi.']);
        }

        if ($value === Recommendation::Pass) {
            $choice = $result->application->choices()->where('quota_id', $quotaId)->first();

            if ($choice === null) {
                throw ValidationException::withMessages(['quota_id' => 'Prodi harus salah satu pilihan pendaftar.']);
            }

            $capacity = (int) ($run->quota_snapshot[$quotaId]['remaining'] ?? 0);
            $used = SelectionRunResult::query()->where('selection_run_id', $run->getKey())->whereKeyNot($result->getKey())->get()
                ->filter(fn (SelectionRunResult $r) => $r->effectiveRecommendation() === 'pass' && $r->effectiveQuotaId() === $quotaId)->count();

            if ($used >= $capacity) {
                throw new ConflictException('Kuota prodi ini sudah penuh pada snapshot.', 'QUOTA_FULL');
            }
        }

        return DB::transaction(function () use ($result, $value, $quotaId, $reason, $actor): SelectionRunResult {
            $result->forceFill([
                'override_recommendation' => $value->value,
                'override_quota_id' => $value === Recommendation::Pass ? $quotaId : null,
                'override_reason' => $reason,
                'override_by' => $actor->getKey(),
            ])->save();
            $this->history->action($result, AuditAction::ForcedOverride, ['recommendation' => $value->value, 'quota_id' => $quotaId], $reason);

            return $result->refresh();
        });
    }

    /**
     * Seri tak terselesaikan (H.5 langkah 4): panitia menetapkan urutan dengan
     * alasan → run BARU dengan urutan manual (hasil lama tidak diubah).
     *
     * @param  array<int, array{application_ids_ordered: array<int, string>, reason: string}>  $groups
     */
    public function resolveTies(SelectionRun $run, array $groups, User $actor): SelectionRun
    {
        if (! in_array($run->status, [RunStatus::Computed, RunStatus::UnderReview], true)) {
            throw new InvalidTransitionException('Urutan seri hanya ditetapkan pada run yang sedang ditinjau.');
        }

        $manual = (array) ($run->summary['manual_order'] ?? []);
        $reasons = [];

        foreach ($groups as $group) {
            if (trim($group['reason'] ?? '') === '') {
                throw ValidationException::withMessages(['groups' => 'Alasan wajib untuk setiap kelompok seri.']);
            }

            foreach (array_values($group['application_ids_ordered']) as $position => $applicationId) {
                $manual[$applicationId] = $position + 1;
            }

            $reasons[] = $group['reason'];
        }

        $this->history->action($run, AuditAction::Updated, ['manual_order' => $manual], implode(' | ', $reasons));

        return $this->create($run->wavePath, $actor, $manual, implode(' | ', $reasons));
    }

    /** "Ajukan pengesahan" (FR-SEL-07, BR-30): tanpa seri tak terselesaikan; hash termasuk override. */
    public function submit(SelectionRun $run, ?string $note, User $actor): SelectionRun
    {
        if ($run->status !== RunStatus::UnderReview) {
            throw new InvalidTransitionException('Mulai peninjauan sebelum mengajukan pengesahan.');
        }

        $unresolved = SelectionRunResult::query()->where('selection_run_id', $run->getKey())->where('tie_unresolved', true)
            ->whereNull('override_recommendation')->exists();

        if ($unresolved) {
            throw new DomainRuleException('Masih ada seri yang belum ditetapkan urutannya.', 'TIES_UNRESOLVED', 422);
        }

        return DB::transaction(function () use ($run, $note, $actor): SelectionRun {
            $run = SelectionRun::query()->whereKey($run->getKey())->lockForUpdate()->firstOrFail();
            $run->forceFill([
                'status' => RunStatus::PendingApproval,
                'submitted_by' => $actor->getKey(),
                'review_note' => $note,
                'result_hash' => $this->currentHash($run),
            ])->save();

            $approval = $this->approvals->submit($run, PmbApprovalService::RESULT_APPROVAL, $actor, $note, [
                'path_category' => $run->wavePath->admissionPath?->category->value,
            ]);
            $run->forceFill(['approval_request_id' => $approval->getKey()])->save();
            $this->history->action($run, AuditAction::Updated, ['status' => 'pending_approval', 'approval_request_id' => $approval->getKey()], $note);
            $this->outbox->notifyPermission($run, PmbPermission::RESULTS_APPROVE, 'pmb.approval.requested', [
                'path_name' => $run->wavePath->admissionPath?->name,
                'wave_name' => $run->wavePath->wave?->name,
            ]);

            return $run->refresh();
        });
    }

    /**
     * Listener approval disetujui: run approved, keputusan `unpublished`
     * dibuat dari hasil efektif (hitung + override), nilai dikunci, status
     * seleksi → ranked, batch publikasi dijadwalkan (FR-ANN-03).
     */
    public function onApproved(SelectionRun $run): void
    {
        DB::transaction(function () use ($run): void {
            $run = SelectionRun::query()->whereKey($run->getKey())->lockForUpdate()->firstOrFail();

            if ($run->status !== RunStatus::PendingApproval) {
                return;
            }

            if ($run->result_hash !== $this->currentHash($run)) {
                $run->forceFill(['status' => RunStatus::UnderReview, 'review_note' => 'Hash hasil berubah setelah pengajuan — wajib ditinjau ulang.'])->save();

                return;
            }

            $run->forceFill(['status' => RunStatus::Approved, 'approved_at' => now()])->save();
            $isCorrection = $run->run_type === 'correction';

            foreach (SelectionRunResult::query()->with(['application.choices', 'assignedQuota'])->where('selection_run_id', $run->getKey())->get() as $result) {
                $this->createDecision($run, $result, $isCorrection);
            }

            $this->scores->lockForRun(SelectionRunResult::query()->where('selection_run_id', $run->getKey())->pluck('application_id')->all(), (string) $run->getKey());

            $wave = $run->wavePath->wave;
            $publishAt = $isCorrection || $wave->is_ods || $wave->announcement_at->isPast() ? now() : $wave->announcement_at;

            AnnouncementBatch::query()->create([
                'selection_run_id' => $run->getKey(),
                'scheduled_publish_at' => $publishAt,
                'status' => 'scheduled',
            ]);

            $this->history->action($run, AuditAction::Approved, ['scheduled_publish_at' => $publishAt->toIso8601String()]);
        });
    }

    public function onRejected(SelectionRun $run, bool $returned): void
    {
        $run = $run->refresh();

        if ($run->status !== RunStatus::PendingApproval) {
            return;
        }

        $target = $returned ? RunStatus::UnderReview : RunStatus::Rejected;
        $run->forceFill(['status' => $target])->save();
        $this->history->action($run, $returned ? AuditAction::Returned : AuditAction::Rejected, ['status' => $target->value]);
    }

    /** Hash efektif (hasil + override) — diverifikasi ulang sebelum publikasi (J.3). */
    public function currentHash(SelectionRun $run): string
    {
        $rows = SelectionRunResult::query()->where('selection_run_id', $run->getKey())->get()->map(fn (SelectionRunResult $r) => [
            'application_id' => $r->application_id,
            'final_score_rounded' => $r->final_score_rounded,
            'recommendation' => $r->recommendation->value,
            'assigned_quota_id' => $r->assigned_quota_id,
            'waitlist_entries' => $r->waitlist_entries ?? [],
            'override_recommendation' => $r->override_recommendation,
            'override_quota_id' => $r->override_quota_id,
        ])->all();

        return SelectionAllocator::hash($rows);
    }

    private function createDecision(SelectionRun $run, SelectionRunResult $result, bool $isCorrection): void
    {
        $application = $result->application;
        $recommendation = $result->effectiveRecommendation();
        $quotaId = $result->effectiveQuotaId();
        $quota = $quotaId ? Quota::query()->find($quotaId) : null;
        $waitlist = $result->override_recommendation !== null ? [] : (array) ($result->waitlist_entries ?? []);

        $decision = match ($recommendation) {
            'pass' => Decision::Accepted,
            'waitlist' => Decision::Waitlisted,
            default => Decision::Rejected,
        };

        if ($decision === Decision::Waitlisted && $waitlist === []) {
            $decision = Decision::Rejected;
        }

        $previous = AdmissionDecision::query()->where('application_id', $application->getKey())->where('is_current', true)->lockForUpdate()->first();

        if ($previous !== null) {
            $previous->forceFill(['is_current' => false])->save();
        }

        AdmissionDecision::query()->create([
            'university_id' => $application->university_id,
            'application_id' => $application->getKey(),
            'decision_version' => ($previous?->decision_version ?? 0) + 1,
            'is_current' => true,
            'decision' => $decision,
            'source' => $isCorrection ? DecisionSource::Correction : DecisionSource::SelectionRun,
            'selection_run_id' => $run->getKey(),
            'run_result_id' => $result->getKey(),
            'quota_id' => $decision === Decision::Accepted ? $quota?->getKey() : ($waitlist[0]['quota_id'] ?? null),
            'study_program_id' => $decision === Decision::Accepted ? $quota?->study_program_id : null,
            'study_system_id' => $decision === Decision::Accepted ? $quota?->study_system_id : null,
            'waitlist_rank' => $decision === Decision::Waitlisted ? ($waitlist[0]['rank'] ?? null) : null,
            'waitlist_entries' => $decision === Decision::Waitlisted ? $waitlist : null,
            'publication_status' => PublicationStatus::Unpublished,
            'correction_note' => $isCorrection ? 'Koreksi hasil (run '.$run->getKey().')' : null,
            // Keputusan beasiswa terpisah (FR-SPC-06) — ditetapkan lewat DecisionService::setScholarshipAward().
            'scholarship_award' => $previous?->scholarship_award,
            'previous_decision_id' => $previous?->getKey(),
        ]);

        if ($application->selection_status !== SelectionStatus::Ranked && $application->selection_status->canTransitionTo(SelectionStatus::Ranked)) {
            $from = $application->selection_status;
            $application->forceFill(['selection_status' => SelectionStatus::Ranked])->save();
            $this->history->record($application, Lifecycle::Selection, $from, SelectionStatus::Ranked, Actor::system(), metadata: ['run_id' => $run->getKey()]);
        }
    }

    /**
     * Snapshot kuota (H.7): sisa = kapasitas − keputusan accepted aktif dari
     * run/gelombang lain; ambang = min_final_score kuota ?? passing_score skema.
     *
     * @param  array<int, string>  $excludeApplications
     * @return array<string, array<string, mixed>>
     */
    private function quotaSnapshot(WavePath $wavePath, mixed $passingScore, array $excludeApplications): array
    {
        $quotas = Quota::query()->where('period_id', $wavePath->wave->period_id)->where('admission_path_id', $wavePath->admission_path_id)->where('is_offered', true)->lockForUpdate()->get();
        $snapshot = [];

        foreach ($quotas as $quota) {
            $occupied = AdmissionDecision::query()->where('quota_id', $quota->getKey())->where('is_current', true)
                ->where('decision', Decision::Accepted->value)
                ->whereNotIn('application_id', $excludeApplications)
                ->where(fn ($q) => $q->whereDoesntHave('application.reRegistration')->orWhereHas('application.reRegistration', fn ($r) => $r->whereNotIn('status', ['declined', 'expired', 'cancelled'])))
                ->count();

            $remaining = max(0, $quota->capacity - $occupied);

            if ($wavePath->quota_mode->value === 'wave_cap' && isset($quota->wave_caps[$wavePath->wave_id])) {
                $waveAccepted = AdmissionDecision::query()->where('quota_id', $quota->getKey())->where('is_current', true)->where('decision', Decision::Accepted->value)
                    ->whereHas('application', fn ($q) => $q->where('wave_id', $wavePath->wave_id))->whereNotIn('application_id', $excludeApplications)->count();
                $remaining = min($remaining, max(0, (int) $quota->wave_caps[$wavePath->wave_id] - $waveAccepted));
            }

            $snapshot[(string) $quota->getKey()] = [
                'study_program_id' => $quota->study_program_id,
                'study_system_id' => $quota->study_system_id,
                'capacity' => $quota->capacity,
                'occupied_elsewhere' => $occupied,
                'remaining' => $remaining,
                'threshold' => (float) ($quota->min_final_score ?? $passingScore ?? 0),
                'waitlist_capacity' => $quota->waitlist_capacity,
            ];
        }

        return $snapshot;
    }

    /**
     * Nilai mentah per komponen untuk satu aplikasi: rata-rata antarpenguji,
     * status absent dari kehadiran sesi komponen.
     *
     * @param  array<int, \Modules\Admission\Models\SelectionComponent>  $components
     * @param  array<string, array<string, bool>>  $absences
     * @return array<string, array<string, mixed>>
     */
    private function scoreInput(Application $application, array $components, array $absences): array
    {
        $input = [];

        foreach ($components as $component) {
            $scores = $application->scores->where('selection_component_id', $component->getKey());
            $numeric = $scores->whereNotNull('raw_score');
            $passFlags = $scores->whereNotNull('passed');

            $input[$component->code] = [
                'raw' => $numeric->isNotEmpty() ? round($numeric->avg(fn (SelectionScore $s) => (float) $s->raw_score), 4) : null,
                'passed' => $passFlags->isNotEmpty() ? $passFlags->every(fn (SelectionScore $s) => $s->passed) : null,
                'status' => ($absences[$application->getKey()][$component->getKey()] ?? false) && $scores->isEmpty() ? SelectionCalculator::ABSENT : null,
                'session' => $scores->first()?->exam_session_id,
            ];
        }

        return $input;
    }

    /**
     * @param  array<int, string>  $componentIds
     * @return array<string, array<string, bool>>
     */
    private function absences(WavePath $wavePath, array $componentIds): array
    {
        $map = [];

        ExamParticipant::query()->whereIn('attendance_status', [AttendanceStatus::Absent->value, AttendanceStatus::Disqualified->value])
            ->whereHas('schedule', fn ($q) => $q->whereIn('selection_component_id', $componentIds))
            ->with('schedule')->get()
            ->each(function (ExamParticipant $p) use (&$map): void {
                $map[$p->application_id][$p->schedule->selection_component_id] = true;
            });

        return $map;
    }

    /**
     * Statistik sesi untuk normalisasi z-score (H.4.3): hanya sesi ≥ 30 peserta & σ > 0.
     *
     * @param  array<int, \Modules\Admission\Models\SelectionComponent>  $components
     * @param  array<int, string>  $applicationIds
     * @return array<string, array<string, array{mean: float, sd: float, n: int}>>
     */
    private function sessionStats(array $components, array $applicationIds): array
    {
        $stats = [];

        foreach ($components as $component) {
            if ($component->normalization->value !== 'zscore_session') {
                continue;
            }

            $groups = SelectionScore::query()->where('selection_component_id', $component->getKey())->whereNotNull('raw_score')->whereNotNull('exam_session_id')
                ->get()->groupBy('exam_session_id');

            foreach ($groups as $sessionId => $scores) {
                $values = $scores->map(fn ($s) => (float) $s->raw_score);
                $n = $values->count();
                $mean = $values->avg();
                $sd = $n > 1 ? sqrt($values->map(fn ($v) => ($v - $mean) ** 2)->sum() / ($n - 1)) : 0.0;

                if ($n < 30 || $sd <= 0) {
                    throw new DomainRuleException("Normalisasi z-score komponen {$component->code} memerlukan ≥ 30 peserta dan simpangan baku > 0 per sesi.", 'ZSCORE_NOT_APPLICABLE', 422);
                }

                $stats[$component->code][(string) $sessionId] = ['mean' => $mean, 'sd' => $sd, 'n' => $n];
            }
        }

        return $stats;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<string, array<string, mixed>>  $quotas
     * @return array<string, mixed>
     */
    private function summary(array $rows, array $quotas): array
    {
        $byRecommendation = array_count_values(array_map(fn ($r) => $r['recommendation'], $rows));
        $failReasons = array_count_values(array_filter(array_map(fn ($r) => $r['fail_reason'] ?? $r['ineligible_reason'], $rows)));
        $histogram = array_fill(0, 10, 0);

        foreach ($rows as $row) {
            if ($row['final_score_rounded'] !== null) {
                $histogram[min(9, (int) floor((float) $row['final_score_rounded'] / 10))]++;
            }
        }

        $perQuota = [];

        foreach ($quotas as $quotaId => $quota) {
            $perQuota[$quotaId] = [
                ...$quota,
                'assigned' => count(array_filter($rows, fn ($r) => $r['assigned_quota_id'] === $quotaId)),
                'waitlisted' => count(array_filter($rows, fn ($r) => collect($r['waitlist_entries'] ?? [])->contains('quota_id', $quotaId))),
            ];
        }

        return [
            'total' => count($rows),
            'eligible' => count(array_filter($rows, fn ($r) => $r['eligible'])),
            'by_recommendation' => $byRecommendation,
            'fail_reasons' => $failReasons,
            'histogram' => $histogram,
            'per_quota' => $perQuota,
            'ties_unresolved' => count(array_filter($rows, fn ($r) => $r['tie_unresolved'])),
        ];
    }

    private function assertScoresReady(WavePath $wavePath): void
    {
        $components = $wavePath->selectionScheme->components()->get();
        $incomplete = [];

        // Komponen turunan (administrasi/rapor/prestasi) dihitung ulang lebih dulu agar konsisten.
        if ($components->contains(fn ($c) => in_array($c->source, [ComponentSource::ReportCard, ComponentSource::Achievement, ComponentSource::Administrative], true))) {
            Application::query()->where('wave_path_id', $wavePath->getKey())->where('verification_status', VerificationStatus::Verified->value)
                ->whereNotIn('selection_status', [SelectionStatus::Ranked->value])->get()
                ->each(fn (Application $application) => $this->scores->computeDerived($application));
        }

        $applications = Application::query()->with('scores')->where('wave_path_id', $wavePath->getKey())
            ->whereIn('selection_status', [SelectionStatus::Eligible->value, SelectionStatus::Scheduled->value])->get();

        foreach ($applications as $application) {
            foreach ($components->where('is_mandatory', true) as $component) {
                $has = $application->scores->where('selection_component_id', $component->getKey())->contains(fn ($s) => $s->raw_score !== null || $s->passed !== null);

                if (! $has) {
                    $incomplete[] = ['application_id' => $application->getKey(), 'registration_number' => $application->registration_number, 'component' => $component->code];
                }
            }
        }

        $unlockedSessions = ExamSession::query()->whereNull('scores_locked_at')
            ->whereHas('schedule', fn ($q) => $q->whereIn('selection_component_id', $components->pluck('id'))->where('status', '!=', 'cancelled')
                ->whereJsonContains('wave_path_ids', $wavePath->getKey()))
            ->where('participants_count', '>', 0)->pluck('code')->all();

        if ($incomplete !== [] || $unlockedSessions !== []) {
            throw new DomainRuleException('Nilai belum lengkap atau sesi belum dikunci.', 'INCOMPLETE_SCORES', 422, [
                'incomplete_scores' => array_slice($incomplete, 0, 50),
                'incomplete_count' => count($incomplete),
                'unlocked_sessions' => $unlockedSessions,
            ]);
        }

    }
}
