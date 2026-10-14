<?php

namespace Modules\Admission\Services\Exam;

use App\Support\Http\Exceptions\ConflictException;
use App\Support\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Admission\Enums\AttendanceStatus;
use Modules\Admission\Enums\ExamMode;
use Modules\Admission\Enums\ExamScheduleStatus;
use Modules\Admission\Exceptions\DomainRuleException;
use Modules\Admission\Models\CbtAttempt;
use Modules\Admission\Models\CbtQuestion;
use Modules\Admission\Models\ExamParticipant;
use Modules\Admission\Models\ExamSchedule;
use Modules\Admission\Services\Selection\ScoreService;

/**
 * CBT seleksi PMB (FR-EXM-09, 11 §B.5 opsi internal): bank soal pilihan
 * ganda per jadwal mode `cbt`, urutan acak per peserta yang dibekukan saat
 * mulai, batas waktu di server, penilaian otomatis → nilai `cbt_sync`.
 * Juga menerima nilai dari CBT eksternal lewat webhook bertanda tangan.
 */
class CbtService
{
    public function __construct(private readonly ScoreService $scores) {}

    /** @param  array<string, mixed>  $data */
    public function saveQuestion(ExamSchedule $schedule, ?CbtQuestion $question, array $data): CbtQuestion
    {
        $this->assertCbtSchedule($schedule);

        if (CbtAttempt::query()->whereHas('participant', fn ($q) => $q->where('schedule_id', $schedule->getKey()))->exists()) {
            throw new ConflictException('Soal tidak dapat diubah setelah ujian dimulai.', 'IN_USE');
        }

        $options = array_values((array) ($data['options'] ?? $question?->options ?? []));
        $keys = array_map(fn ($o) => (string) ($o['key'] ?? ''), $options);

        if (count($options) < 2 || in_array('', $keys, true) || count(array_unique($keys)) !== count($keys)) {
            throw ValidationException::withMessages(['options' => 'Minimal dua opsi dengan kunci unik (A, B, ...).']);
        }

        $correct = (string) ($data['correct_option'] ?? $question?->correct_option);

        if (! in_array($correct, $keys, true)) {
            throw ValidationException::withMessages(['correct_option' => 'Kunci jawaban harus salah satu opsi.']);
        }

        $question ??= new CbtQuestion(['schedule_id' => $schedule->getKey()]);
        $question->forceFill([
            ...Arr::only($data, ['question', 'points', 'sort_order']),
            'options' => array_map(fn ($o) => ['key' => (string) $o['key'], 'text' => (string) ($o['text'] ?? '')], $options),
            'correct_option' => $correct,
        ])->save();

        return $question->refresh();
    }

    public function deleteQuestion(CbtQuestion $question): void
    {
        if (CbtAttempt::query()->whereHas('participant', fn ($q) => $q->where('schedule_id', $question->schedule_id))->exists()) {
            throw new ConflictException('Soal tidak dapat dihapus setelah ujian dimulai.', 'IN_USE');
        }

        $question->delete();
    }

    /**
     * Mulai ujian: hanya dalam jendela sesi; urutan soal dibekukan; batas =
     * min(mulai + durasi, akhir sesi). Pemanggilan ulang mengembalikan attempt yang sama.
     *
     * @return array<string, mixed>
     */
    public function start(ExamParticipant $participant): array
    {
        $schedule = $participant->schedule;
        $session = $participant->session;
        $this->assertCbtSchedule($schedule);

        if ($schedule->status !== ExamScheduleStatus::Published) {
            throw new DomainRuleException('Ujian belum dibuka.', 'EXAM_NOT_OPEN', 422);
        }

        if (in_array($participant->attendance_status, [AttendanceStatus::Absent, AttendanceStatus::Disqualified, AttendanceStatus::Excused], true)) {
            throw new DomainRuleException('Anda tidak dapat mengikuti sesi ini.', 'NOT_ALLOWED', 403);
        }

        $now = CarbonImmutable::now();

        if ($now->lt($session->starts_at) || $now->gte($session->ends_at)) {
            throw new DomainRuleException('Ujian hanya dapat dikerjakan pada waktu sesi.', 'OUTSIDE_SESSION_WINDOW', 422);
        }

        $attempt = DB::transaction(function () use ($participant, $schedule, $session, $now): CbtAttempt {
            $existing = CbtAttempt::query()->where('participant_id', $participant->getKey())->lockForUpdate()->first();

            if ($existing !== null) {
                return $existing;
            }

            $ids = $schedule->cbtQuestions()->pluck('id')->all();

            if ($ids === []) {
                throw new DomainRuleException('Bank soal belum tersedia.', 'NO_QUESTIONS', 422);
            }

            if ($schedule->cbt_randomize) {
                shuffle($ids);
            }

            if ($schedule->cbt_question_count) {
                $ids = array_slice($ids, 0, $schedule->cbt_question_count);
            }

            $duration = (int) ($schedule->cbt_duration_minutes ?: $session->starts_at->diffInMinutes($session->ends_at));
            $deadline = $now->addMinutes($duration);
            $deadline = $deadline->gt($session->ends_at) ? CarbonImmutable::parse($session->ends_at) : $deadline;

            $participant->forceFill(['attendance_status' => AttendanceStatus::Present, 'checked_in_at' => $now])->save();

            return CbtAttempt::query()->create([
                'participant_id' => $participant->getKey(),
                'question_order' => $ids,
                'answers' => [],
                'started_at' => $now,
                'deadline_at' => $deadline,
            ]);
        });

        return $this->present($attempt);
    }

    public function answer(CbtAttempt $attempt, string $questionId, ?string $option): CbtAttempt
    {
        // Dicek di luar transaksi: auto-submit saat waktu habis tidak boleh ikut ter-rollback.
        $this->assertOpen($attempt);

        return DB::transaction(function () use ($attempt, $questionId, $option): CbtAttempt {
            $attempt = CbtAttempt::query()->whereKey($attempt->getKey())->lockForUpdate()->firstOrFail();

            if ($attempt->submitted_at !== null || $attempt->deadline_at->isPast()) {
                throw new DomainRuleException('Ujian sudah berakhir.', 'ALREADY_SUBMITTED', 409);
            }

            if (! in_array($questionId, (array) $attempt->question_order, true)) {
                throw ValidationException::withMessages(['question_id' => 'Soal bukan bagian ujian Anda.']);
            }

            $answers = (array) $attempt->answers;
            $answers[$questionId] = $option;
            $attempt->forceFill(['answers' => $answers])->save();

            return $attempt;
        });
    }

    public function recordViolation(CbtAttempt $attempt): void
    {
        if ($attempt->submitted_at === null) {
            $attempt->increment('violations');
        }
    }

    /** Selesai (atau otomatis saat batas waktu): nilai = poin benar diskalakan ke rentang komponen. */
    public function submit(CbtAttempt $attempt): CbtAttempt
    {
        $attempt = DB::transaction(function () use ($attempt): CbtAttempt {
            $attempt = CbtAttempt::query()->whereKey($attempt->getKey())->lockForUpdate()->firstOrFail();

            if ($attempt->submitted_at !== null) {
                return $attempt;
            }

            $questions = CbtQuestion::query()->whereIn('id', (array) $attempt->question_order)->get()->keyBy('id');
            $total = 0.0;
            $correct = 0.0;

            foreach ((array) $attempt->question_order as $id) {
                $question = $questions->get($id);

                if ($question === null) {
                    continue;
                }

                $total += (float) $question->points;

                if (($attempt->answers[$id] ?? null) === $question->correct_option) {
                    $correct += (float) $question->points;
                }
            }

            $attempt->forceFill(['submitted_at' => now(), 'raw_score' => $correct])->save();

            $participant = $attempt->participant;
            $component = $participant->schedule->component;
            $span = (float) $component->raw_max - (float) $component->raw_min;
            $raw = (float) $component->raw_min + ($total > 0 ? $correct / $total : 0) * $span;
            $this->scores->recordCbtScore($participant->application, $component, $raw, (string) $participant->session_id, 'CBT internal: '.$correct.'/'.$total.' poin');

            return $attempt->refresh();
        });

        return $attempt;
    }

    /** Job: attempt melewati batas waktu tanpa dikirim → dikirim otomatis. */
    public function autoSubmitExpired(): int
    {
        $count = 0;

        CbtAttempt::query()->whereNull('submitted_at')->where('deadline_at', '<', now())->get()
            ->each(function (CbtAttempt $attempt) use (&$count): void {
                $this->submit($attempt);
                $count++;
            });

        return $count;
    }

    /** @return array<string, mixed> */
    public function present(CbtAttempt $attempt): array
    {
        $questions = CbtQuestion::query()->whereIn('id', (array) $attempt->question_order)->get()->keyBy('id');

        return [
            'attempt_id' => $attempt->getKey(),
            'started_at' => $attempt->started_at->toIso8601String(),
            'deadline_at' => $attempt->deadline_at->toIso8601String(),
            'submitted_at' => $attempt->submitted_at?->toIso8601String(),
            'server_time' => now()->toIso8601String(),
            'questions' => array_values(array_filter(array_map(fn ($id) => $questions->has($id) ? [
                'id' => $id,
                'question' => $questions[$id]->question,
                'options' => $questions[$id]->options,
                'answer' => $attempt->answers[$id] ?? null,
            ] : null, (array) $attempt->question_order))),
        ];
    }

    /**
     * Webhook CBT eksternal: header X-Signature = HMAC-SHA256(body, secret);
     * body {schedule_reference, results: [{participant_number, score}]}.
     *
     * @return array{applied: int, skipped: int}
     */
    public function receiveExternal(Request $request): array
    {
        $secret = (string) config('pmb.cbt.webhook_secret');
        $expected = hash_hmac('sha256', $request->getContent(), $secret);

        if ($secret === '' || ! hash_equals($expected, (string) $request->header('X-Signature'))) {
            abort(401, 'Tanda tangan tidak valid.');
        }

        $schedule = ExamSchedule::query()->withoutGlobalScopes()->where('external_reference', (string) $request->input('schedule_reference'))->firstOrFail();
        app(TenantContext::class)->setUniversityId((string) $schedule->university_id);
        $applied = 0;
        $skipped = 0;

        foreach ((array) $request->input('results', []) as $row) {
            $participant = ExamParticipant::query()->where('schedule_id', $schedule->getKey())->where('participant_number', (string) ($row['participant_number'] ?? ''))->first();

            if ($participant === null || ! is_numeric($row['score'] ?? null)) {
                $skipped++;

                continue;
            }

            $this->scores->recordCbtScore($participant->application, $schedule->component, (float) $row['score'], (string) $participant->session_id, 'CBT eksternal');
            $participant->forceFill(['attendance_status' => AttendanceStatus::Present])->save();
            $applied++;
        }

        return ['applied' => $applied, 'skipped' => $skipped];
    }

    private function assertOpen(CbtAttempt $attempt): void
    {
        if ($attempt->submitted_at !== null) {
            throw new DomainRuleException('Ujian sudah dikirim.', 'ALREADY_SUBMITTED', 409);
        }

        if ($attempt->deadline_at->isPast()) {
            $this->submit($attempt);

            throw new DomainRuleException('Waktu ujian telah habis.', 'DEADLINE_PASSED', 422);
        }
    }

    private function assertCbtSchedule(ExamSchedule $schedule): void
    {
        if ($schedule->mode !== ExamMode::Cbt) {
            throw new DomainRuleException('Jadwal ini bukan ujian CBT.', 'NOT_CBT', 422);
        }
    }
}
