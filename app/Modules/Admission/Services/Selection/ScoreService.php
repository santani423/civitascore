<?php

namespace Modules\Admission\Services\Selection;

use App\Models\User;
use App\Support\Http\Exceptions\ConflictException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Admission\Enums\AttendanceStatus;
use Modules\Admission\Enums\ChangeRequestStatus;
use Modules\Admission\Enums\ChangeRequestType;
use Modules\Admission\Enums\ComponentSource;
use Modules\Admission\Enums\DocumentStatus;
use Modules\Admission\Enums\Lifecycle;
use Modules\Admission\Enums\ScoreSource;
use Modules\Admission\Enums\SelectionStatus;
use Modules\Admission\Exceptions\DomainRuleException;
use Modules\Admission\Models\Application;
use Modules\Admission\Models\ChangeRequest;
use Modules\Admission\Models\ExamParticipant;
use Modules\Admission\Models\ExamSchedule;
use Modules\Admission\Models\ExamSession;
use Modules\Admission\Models\ScoreImport;
use Modules\Admission\Models\ScoreRevision;
use Modules\Admission\Models\SelectionComponent;
use Modules\Admission\Models\SelectionScore;
use Modules\Admission\Selection\SelectionCalculator;
use Modules\Admission\Services\Approval\PmbApprovalService;
use Modules\Admission\Support\Actor;
use Modules\Admission\Support\HistoryRecorder;
use Modules\Admission\Support\PmbFileStorage;
use Modules\Admission\Support\StateTransition;
use Modules\Admission\Support\SimpleXlsx;

/**
 * Nilai seleksi (06 §H.8, FR-SEL-02, FR-EXM-07): input penguji (rubrik,
 * multi-penguji), impor validasi→commit atomik, kunci per sesi, nilai
 * turunan rapor/prestasi/administrasi, riwayat revisi & koreksi ber-approval.
 */
class ScoreService
{
    public function __construct(
        private readonly HistoryRecorder $history,
        private readonly PmbFileStorage $storage,
        private readonly PmbApprovalService $approvals,
    ) {}

    /**
     * Nilai turunan setelah verifikasi (FR-SEL-02): administrasi (lulus),
     * rapor (rata-rata semester/mapel terpilih), prestasi (poin berdokumen approved).
     */
    public function computeDerived(Application $application): void
    {
        $scheme = $application->wavePath?->selectionScheme;

        if ($scheme === null) {
            return;
        }

        foreach ($scheme->components as $component) {
            $value = match ($component->source) {
                ComponentSource::Administrative => ['raw' => null, 'passed' => $application->verification_status->value === 'verified'],
                ComponentSource::ReportCard => ['raw' => $this->reportCardRaw($application, $component), 'passed' => null],
                ComponentSource::Achievement => ['raw' => $this->achievementRaw($application, $component), 'passed' => null],
                default => null,
            };

            if ($value === null) {
                continue;
            }

            $existing = SelectionScore::query()->where('application_id', $application->getKey())->where('selection_component_id', $component->getKey())->whereNull('examiner_user_id')->first();

            if ($existing?->locked_at !== null) {
                continue;
            }

            $this->write($application, $component, null, $value['raw'], $value['passed'], null, ScoreSource::Computed, null, null, 'Dihitung otomatis setelah verifikasi');
        }

        $this->refreshAssessed($application);
    }

    /**
     * Koreksi nilai turunan oleh panitia (alasan wajib) sebelum terkunci.
     */
    public function correct(SelectionScore $score, ?float $raw, ?bool $passed, string $reason, User $actor): SelectionScore
    {
        if ($score->locked_at !== null) {
            throw new DomainRuleException('Nilai sudah terkunci; ajukan koreksi.', 'SCORES_LOCKED', 423);
        }

        if ($score->exam_session_id && ExamSession::query()->whereKey($score->exam_session_id)->whereNotNull('scores_locked_at')->exists()) {
            throw new DomainRuleException('Nilai sesi sudah dikunci.', 'SCORES_LOCKED', 423);
        }

        $this->assertRange($score->component, $raw, $passed);

        return $this->write($score->application, $score->component, $score->examiner_user_id, $raw, $passed, $score->rubric_scores, ScoreSource::Manual, $score->exam_session_id, $actor, $reason);
    }

    /**
     * Input nilai oleh penguji yang ditugaskan (FR-EXM-07, AC-10): hanya
     * peserta sesi yang ditugaskan, sebelum sesi dikunci.
     *
     * @param  array<int, array{participant_id: string, raw_score?: float|int|string|null, rubric_scores?: array<string, float|int>|null, passed?: bool|null, note?: string|null}>  $rows
     */
    public function inputSessionScores(ExamSession $session, array $rows, User $examiner): int
    {
        if ($session->scores_locked_at !== null) {
            throw new DomainRuleException('Nilai sesi sudah dikunci.', 'SCORES_LOCKED', 423);
        }

        $isAssigned = $session->examiners()->where('user_id', $examiner->getKey())->whereIn('role', ['examiner', 'interviewer'])->exists();

        if (! $isAssigned) {
            abort(403, 'Anda tidak ditugaskan sebagai penguji pada sesi ini.');
        }

        $component = $session->schedule->component;
        $multiExaminer = in_array($component->source, [ComponentSource::Interview, ComponentSource::Portfolio], true);
        $count = 0;

        DB::transaction(function () use ($session, $rows, $examiner, $component, $multiExaminer, &$count): void {
            foreach ($rows as $row) {
                $participant = ExamParticipant::query()->whereKey($row['participant_id'])->where('session_id', $session->getKey())->first();

                if ($participant === null) {
                    abort(403, 'Peserta bukan anggota sesi ini.');
                }

                if (in_array($participant->attendance_status, [AttendanceStatus::Absent, AttendanceStatus::Excused, AttendanceStatus::Disqualified], true)) {
                    continue;
                }

                $rubric = $row['rubric_scores'] ?? null;
                $raw = isset($row['raw_score']) && $row['raw_score'] !== '' ? (float) $row['raw_score'] : null;

                if ($rubric !== null && ! empty($component->rubric['criteria'])) {
                    $raw = SelectionCalculator::rubricRaw([$rubric], $component->rubric['criteria'], (float) $component->raw_max - (float) $component->raw_min)['raw'];
                }

                $this->assertRange($component, $raw, $row['passed'] ?? null);
                $this->write($participant->application, $component, $multiExaminer ? (string) $examiner->getKey() : null, $raw, $row['passed'] ?? null, $rubric, ScoreSource::Manual, (string) $session->getKey(), $examiner, $row['note'] ?? 'Input penguji', $row['note'] ?? null);

                if ($participant->attendance_status === AttendanceStatus::Registered) {
                    $participant->forceFill(['attendance_status' => AttendanceStatus::Present])->save();
                }

                $count++;
            }
        });

        Application::query()->whereIn('id', ExamParticipant::query()->where('session_id', $session->getKey())->select('application_id'))->get()
            ->each(fn (Application $a) => $this->refreshAssessed($a));

        return $count;
    }

    /**
     * Impor tahap 1 — validasi baris tanpa menulis nilai (FR-EXM-07):
     * peserta ada di jadwal, nilai dalam rentang; laporan baris gagal.
     */
    public function validateImport(ExamSchedule $schedule, SelectionComponent $component, UploadedFile $file, User $actor): ScoreImport
    {
        if ($schedule->selection_component_id !== $component->getKey()) {
            throw ValidationException::withMessages(['selection_component_id' => 'Komponen tidak sesuai jadwal.']);
        }

        $upload = $this->storage->store($file, ['text/csv', 'text/plain', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip'], 5120, ['score-imports', (string) $schedule->getKey()], uploadedByUserId: (string) $actor->getKey());
        $rows = $this->readRows($upload->extension, $this->storage->contents($upload));
        $participants = ExamParticipant::query()->where('schedule_id', $schedule->getKey())->get()->keyBy('participant_number');
        $valid = [];
        $errors = [];

        foreach ($rows as $index => $row) {
            $line = $index + 2;
            $number = trim((string) ($row['participant_number'] ?? ''));
            $participant = $participants->get($number);

            if ($participant === null) {
                $errors[] = ['row' => $line, 'participant_number' => $number, 'message' => 'Nomor peserta tidak terdaftar pada jadwal ini.'];

                continue;
            }

            if ($component->score_type->value === 'pass_fail') {
                $passed = filter_var($row['score'] ?? null, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

                if ($passed === null && ! in_array(mb_strtolower((string) ($row['score'] ?? '')), ['lulus', 'tidak lulus'], true)) {
                    $errors[] = ['row' => $line, 'participant_number' => $number, 'message' => 'Nilai lulus/gagal tidak valid.'];

                    continue;
                }

                $valid[] = ['participant_id' => $participant->getKey(), 'raw' => null, 'passed' => $passed ?? mb_strtolower((string) $row['score']) === 'lulus'];

                continue;
            }

            if (! is_numeric(str_replace(',', '.', (string) ($row['score'] ?? '')))) {
                $errors[] = ['row' => $line, 'participant_number' => $number, 'message' => 'Nilai bukan angka.'];

                continue;
            }

            $raw = (float) str_replace(',', '.', (string) $row['score']);

            if ($raw < (float) $component->raw_min || $raw > (float) $component->raw_max) {
                $errors[] = ['row' => $line, 'participant_number' => $number, 'message' => "Nilai di luar rentang {$component->raw_min}–{$component->raw_max}."];

                continue;
            }

            $valid[] = ['participant_id' => $participant->getKey(), 'raw' => $raw, 'passed' => null];
        }

        return ScoreImport::query()->create([
            'schedule_id' => $schedule->getKey(),
            'selection_component_id' => $component->getKey(),
            'file_upload_id' => $upload->getKey(),
            'valid_rows' => $valid,
            'errors' => $errors,
            'fingerprint' => $this->scheduleFingerprint($schedule),
            'status' => 'validated',
            'created_by' => $actor->getKey(),
        ]);
    }

    /** Impor tahap 2 — commit atomik; ditolak bila data peserta berubah sejak validasi (409). */
    public function commitImport(ScoreImport $import, User $actor): int
    {
        if ($import->status !== 'validated') {
            throw new ConflictException('Impor sudah diterapkan atau dibatalkan.', 'ALREADY_COMMITTED');
        }

        if ($import->fingerprint !== $this->scheduleFingerprint($import->schedule)) {
            throw new ConflictException('Data peserta/nilai berubah sejak validasi. Validasi ulang berkas.', 'DATA_CHANGED');
        }

        $count = 0;

        DB::transaction(function () use ($import, $actor, &$count): void {
            foreach ($import->valid_rows as $row) {
                $participant = ExamParticipant::query()->findOrFail($row['participant_id']);
                $session = $participant->session;

                if ($session->scores_locked_at !== null) {
                    throw new DomainRuleException("Nilai sesi {$session->code} sudah dikunci.", 'SCORES_LOCKED', 423);
                }

                $this->write($participant->application, $import->component, null, $row['raw'], $row['passed'], null, ScoreSource::Import, (string) $session->getKey(), $actor, 'Impor nilai '.$import->getKey());

                if ($participant->attendance_status === AttendanceStatus::Registered) {
                    $participant->forceFill(['attendance_status' => AttendanceStatus::Present])->save();
                }

                $count++;
            }

            $import->forceFill(['status' => 'committed', 'committed_at' => now()])->save();
        });

        Application::query()->whereIn('id', ExamParticipant::query()->where('schedule_id', $import->schedule_id)->select('application_id'))->get()
            ->each(fn (Application $a) => $this->refreshAssessed($a));

        return $count;
    }

    /**
     * Kunci nilai sesi (FR-EXM-07): seluruh peserta hadir wajib punya nilai;
     * selisih antarpenguji di atas batas wajib dimoderasi dahulu (H.4.4).
     */
    public function lockSession(ExamSession $session, User $actor): ExamSession
    {
        $component = $session->schedule->component;
        $missing = [];
        $moderation = [];

        foreach ($session->participants()->with('application')->get() as $participant) {
            if ($participant->attendance_status !== AttendanceStatus::Present) {
                continue;
            }

            $scores = SelectionScore::query()->where('application_id', $participant->application_id)->where('selection_component_id', $component->getKey())->get();

            if ($scores->isEmpty() || $scores->every(fn ($s) => $s->raw_score === null && $s->passed === null)) {
                $missing[] = $participant->participant_number;

                continue;
            }

            if ($scores->count() > 1 && ! empty($component->rubric['criteria'])) {
                $gap = $scores->max('raw_score') - $scores->min('raw_score');
                $limit = ((float) ($component->config['max_examiner_gap'] ?? 20) / 100) * ((float) $component->raw_max - (float) $component->raw_min);

                if ($gap > $limit && empty($component->config['moderation_waived'])) {
                    $moderation[] = $participant->participant_number;
                }
            }
        }

        if ($missing !== []) {
            throw ValidationException::withMessages(['scores' => 'Peserta hadir tanpa nilai: '.implode(', ', array_slice($missing, 0, 20))]);
        }

        if ($moderation !== []) {
            throw ValidationException::withMessages(['scores' => 'Selisih nilai antarpenguji perlu moderasi: '.implode(', ', array_slice($moderation, 0, 20))]);
        }

        $session->forceFill(['scores_locked_at' => now(), 'scores_locked_by' => $actor->getKey()])->save();
        $this->history->action($session, \Modules\AuditLog\Enums\AuditAction::Updated, ['scores_locked' => true]);

        return $session->refresh();
    }

    /**
     * Nilai CBT (FR-EXM-09): internal maupun webhook eksternal; sumber
     * `cbt_sync` tidak dapat diubah manual (hanya lewat koreksi).
     */
    public function recordCbtScore(Application $application, SelectionComponent $component, float $raw, ?string $sessionId, string $reason): SelectionScore
    {
        $raw = max((float) $component->raw_min, min((float) $component->raw_max, round($raw, 4)));
        $score = $this->write($application, $component, null, $raw, null, null, ScoreSource::CbtSync, $sessionId, null, $reason);
        $this->refreshAssessed($application);

        return $score;
    }

    /** Kunci nilai saat run disetujui (H.7). */
    public function lockForRun(array $applicationIds, string $runId): void
    {
        SelectionScore::query()->whereIn('application_id', $applicationIds)->whereNull('locked_at')
            ->update(['locked_at' => now(), 'locked_by_run_id' => $runId]);
    }

    /**
     * Ajukan koreksi nilai terkunci (H.8): approval `pmb_score_correction`;
     * bila disetujui → nilai diubah + riwayat, lalu run koreksi diperlukan.
     */
    public function requestLockedCorrection(SelectionScore $score, ?float $newRaw, ?bool $newPassed, string $reason, ?string $evidenceFileId, User $actor): ChangeRequest
    {
        if ($score->locked_at === null) {
            throw new ConflictException('Nilai belum terkunci; koreksi langsung.', 'NOT_LOCKED');
        }

        $this->assertRange($score->component, $newRaw, $newPassed);

        return DB::transaction(function () use ($score, $newRaw, $newPassed, $reason, $evidenceFileId, $actor): ChangeRequest {
            $change = ChangeRequest::query()->create([
                'type' => ChangeRequestType::ScoreCorrection,
                'subject_type' => 'SelectionScore',
                'subject_id' => $score->getKey(),
                'payload' => ['new_raw_score' => $newRaw, 'new_passed' => $newPassed, 'application_id' => $score->application_id],
                'reason' => $reason,
                'evidence_file_id' => $evidenceFileId,
                'status' => ChangeRequestStatus::PendingApproval,
                'requested_by' => $actor->getKey(),
            ]);
            $approval = $this->approvals->submit($change, PmbApprovalService::SCORE_CORRECTION, $actor, $reason);
            $change->forceFill(['approval_request_id' => $approval->getKey()])->save();

            return $change;
        });
    }

    /** Diterapkan listener approval: buka kunci aplikasi terkait & ubah nilai (selection ranked → assessed). */
    public function applyLockedCorrection(ChangeRequest $change): void
    {
        DB::transaction(function () use ($change): void {
            $score = SelectionScore::query()->whereKey($change->subject_id)->lockForUpdate()->firstOrFail();
            $old = ['raw' => $score->raw_score, 'passed' => $score->passed];
            $score->forceFill([
                'raw_score' => $change->payload['new_raw_score'],
                'passed' => $change->payload['new_passed'],
                'locked_at' => null,
                'locked_by_run_id' => null,
            ])->save();

            ScoreRevision::query()->create([
                'selection_score_id' => $score->getKey(),
                'old_raw_score' => $old['raw'],
                'new_raw_score' => $score->raw_score,
                'old_passed' => $old['passed'],
                'new_passed' => $score->passed,
                'reason' => $change->reason,
                'source' => ScoreSource::Manual,
                'approval_request_id' => $change->approval_request_id,
                'changed_by' => $change->requested_by,
            ]);

            $application = $score->application;

            if ($application->selection_status === SelectionStatus::Ranked) {
                $application->forceFill(['selection_status' => SelectionStatus::Assessed])->save();
                $this->history->record($application, Lifecycle::Selection, SelectionStatus::Ranked, SelectionStatus::Assessed, Actor::system(), 'SCORE_CORRECTION', $change->reason, ['change_request_id' => $change->getKey()]);
            }

            $change->forceFill(['status' => ChangeRequestStatus::Applied, 'applied_at' => now()])->save();
        });
    }

    /** Status `assessed` bila semua komponen wajib bernilai (SM-07). */
    public function refreshAssessed(Application $application): void
    {
        $application->refresh();

        if (! in_array($application->selection_status, [SelectionStatus::Eligible, SelectionStatus::Scheduled, SelectionStatus::Absent], true)) {
            return;
        }

        $components = $application->wavePath?->selectionScheme?->components ?? collect();
        $mandatory = $components->where('is_mandatory', true);
        $scored = SelectionScore::query()->where('application_id', $application->getKey())
            ->where(fn ($q) => $q->whereNotNull('raw_score')->orWhereNotNull('passed'))->pluck('selection_component_id')->unique();

        if ($mandatory->isNotEmpty() && $mandatory->every(fn ($c) => $scored->contains($c->getKey()))) {
            $from = $application->selection_status;

            if ($from->canTransitionTo(SelectionStatus::Assessed)) {
                $application->forceFill(['selection_status' => SelectionStatus::Assessed])->save();
                $this->history->record($application, Lifecycle::Selection, $from, SelectionStatus::Assessed, Actor::system());
            }
        }
    }

    private function write(Application $application, SelectionComponent $component, ?string $examinerId, ?float $raw, ?bool $passed, ?array $rubric, ScoreSource $source, ?string $sessionId, ?User $actor, string $reason, ?string $note = null): SelectionScore
    {
        return DB::transaction(function () use ($application, $component, $examinerId, $raw, $passed, $rubric, $source, $sessionId, $actor, $reason, $note): SelectionScore {
            $score = SelectionScore::query()->where('application_id', $application->getKey())->where('selection_component_id', $component->getKey())
                ->when($examinerId, fn ($q) => $q->where('examiner_user_id', $examinerId), fn ($q) => $q->whereNull('examiner_user_id'))
                ->lockForUpdate()->first();

            if ($score?->locked_at !== null) {
                throw new DomainRuleException('Nilai sudah terkunci.', 'SCORES_LOCKED', 423);
            }

            if ($score?->source === ScoreSource::CbtSync && $source !== ScoreSource::CbtSync) {
                throw new DomainRuleException('Nilai CBT tidak dapat diubah manual; ajukan koreksi.', 'SCORES_LOCKED', 423);
            }

            $old = $score ? ['raw' => $score->raw_score, 'passed' => $score->passed] : null;

            if ($score === null) {
                $score = new SelectionScore([
                    'university_id' => $application->university_id,
                    'application_id' => $application->getKey(),
                    'selection_component_id' => $component->getKey(),
                    'examiner_user_id' => $examinerId,
                ]);
            }

            $score->forceFill([
                'raw_score' => $raw,
                'passed' => $passed,
                'rubric_scores' => $rubric,
                'note' => $note ? mb_substr($note, 0, 500) : $score->note,
                'source' => $source,
                'exam_session_id' => $sessionId,
                'entered_by' => $actor?->getKey(),
            ])->save();

            $changed = $old === null || (string) $old['raw'] !== (string) $score->raw_score || $old['passed'] !== $score->passed;

            if ($changed) {
                ScoreRevision::query()->create([
                    'selection_score_id' => $score->getKey(),
                    'old_raw_score' => $old['raw'] ?? null,
                    'new_raw_score' => $score->raw_score,
                    'old_passed' => $old['passed'] ?? null,
                    'new_passed' => $score->passed,
                    'reason' => $reason,
                    'source' => $source,
                    'changed_by' => $actor?->getKey(),
                ]);
            }

            return $score;
        });
    }

    private function assertRange(SelectionComponent $component, ?float $raw, ?bool $passed): void
    {
        if ($component->score_type->value === 'numeric' && $raw !== null && ($raw < (float) $component->raw_min || $raw > (float) $component->raw_max)) {
            // H.4.1: nilai di luar rentang ditolak saat input (bukan dipotong).
            throw ValidationException::withMessages(['raw_score' => "Nilai harus dalam rentang {$component->raw_min}–{$component->raw_max}."]);
        }
    }

    private function reportCardRaw(Application $application, SelectionComponent $component): ?float
    {
        $education = $application->primaryEducation()->with('reportScores')->first();

        if ($education === null) {
            return null;
        }

        if (($component->config['use_gpa'] ?? false) && $education->gpa !== null) {
            return (float) $education->gpa;
        }

        return SelectionCalculator::reportCardRaw($education->reportScores->map(fn ($s) => ['semester' => $s->semester, 'subject_code' => $s->subject_code, 'score' => (float) $s->score])->all(), (array) $component->config);
    }

    private function achievementRaw(Application $application, SelectionComponent $component): float
    {
        $achievements = $application->account->achievements()->with('document')->get()
            ->filter(fn ($a) => $a->document?->status === DocumentStatus::Approved)
            ->map(fn ($a) => ['level' => $a->level, 'rank' => $a->rank])->values()->all();

        return SelectionCalculator::achievementRaw($achievements, (array) $component->config);
    }

    /**
     * @return array<int, array{participant_number: string, score: string}>
     */
    private function readRows(string $extension, string $contents): array
    {
        $rows = [];

        if ($extension === 'xlsx') {
            $path = tempnam(sys_get_temp_dir(), 'pmbimp');
            file_put_contents($path, $contents);

            try {
                $sheet = SimpleXlsx::read($path);
            } finally {
                @unlink($path);
            }

            $header = array_map(fn ($h) => mb_strtolower(trim((string) $h)), array_shift($sheet) ?? []);

            foreach ($sheet as $cells) {
                $rows[] = $this->mapRow($header, $cells);
            }

            return $rows;
        }

        $lines = preg_split('/\R/', trim(preg_replace('/^\xEF\xBB\xBF/', '', $contents) ?? '')) ?: [];
        $delimiter = substr_count($lines[0] ?? '', ';') > substr_count($lines[0] ?? '', ',') ? ';' : ',';
        $header = array_map(fn ($h) => mb_strtolower(trim($h)), str_getcsv(array_shift($lines) ?? '', $delimiter));

        foreach ($lines as $line) {
            if (trim($line) !== '') {
                $rows[] = $this->mapRow($header, str_getcsv($line, $delimiter));
            }
        }

        return $rows;
    }

    /**
     * @param  array<int, string>  $header
     * @param  array<int, string|null>  $cells
     * @return array{participant_number: string, score: string}
     */
    private function mapRow(array $header, array $cells): array
    {
        $numberIndex = collect($header)->search(fn ($h) => in_array($h, ['participant_number', 'no_peserta', 'nomor_peserta', 'no. peserta'], true));
        $scoreIndex = collect($header)->search(fn ($h) => in_array($h, ['score', 'nilai', 'raw_score'], true));

        return [
            'participant_number' => (string) ($cells[$numberIndex === false ? 0 : $numberIndex] ?? ''),
            'score' => (string) ($cells[$scoreIndex === false ? 1 : $scoreIndex] ?? ''),
        ];
    }

    private function scheduleFingerprint(ExamSchedule $schedule): string
    {
        $state = ExamParticipant::query()->where('schedule_id', $schedule->getKey())->orderBy('id')->get(['id', 'session_id', 'attendance_status'])
            ->map(fn ($p) => $p->id.'|'.$p->session_id.'|'.$p->attendance_status->value)->implode(';');
        $locks = ExamSession::query()->where('schedule_id', $schedule->getKey())->orderBy('id')->get(['id', 'scores_locked_at'])
            ->map(fn ($s) => $s->id.'|'.($s->scores_locked_at?->timestamp ?? '-'))->implode(';');

        return hash('sha256', $state.'#'.$locks);
    }
}
