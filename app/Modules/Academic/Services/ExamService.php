<?php

namespace Modules\Academic\Services;

use App\Support\Http\Exceptions\ConflictException;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Academic\Enums\ExamAttemptStatus;
use Modules\Academic\Enums\ExamViolationType;
use Modules\Academic\Enums\KrsItemStatus;
use Modules\Academic\Enums\LetterGrade;
use Modules\Academic\Enums\QuestionSelectionMode;
use Modules\Academic\Models\ClassSection;
use Modules\Academic\Models\Exam;
use Modules\Academic\Models\ExamAttempt;
use Modules\Academic\Models\ExamAttemptAnswer;
use Modules\Academic\Models\ExamGradeRange;
use Modules\Academic\Models\ExamQuestion;
use Modules\Academic\Models\ExamViolation;
use Modules\Academic\Models\KrsItem;
use Modules\Academic\Models\Student;

/**
 * Aturan bisnis modul Ujian (RANCANGAN-APLIKASI.md §4.18, spec konfigurasi
 * ujian pilihan ganda lanjutan) — question pool, seleksi/pengacakan soal dan
 * opsi jawaban, publikasi, serta penugasan+stabilitas percobaan ujian per
 * peserta. Backend adalah satu-satunya sumber kebenaran untuk penugasan
 * soal, urutan, dan jawaban benar; lihat startAttempt()/answerAttempt().
 */
class ExamService
{
    /**
     * @param  array<string, mixed>  $data  Divalidasi StoreExamRequest.
     */
    public function createExam(array $data): Exam
    {
        // Existence + tenant isolation for class_section_id is checked via a
        // scoped findOrFail() rather than Rule::exists in the Request — a
        // raw exists() query would bypass TenantScoped's global scope and
        // could leak cross-tenant existence (same reasoning as
        // StoreKrsItemRequest).
        ClassSection::query()->findOrFail((string) $data['class_section_id']);

        $data['created_by'] ??= Auth::id();

        return Exam::query()->create($data);
    }

    /**
     * @param  array<string, mixed>  $data  Divalidasi UpdateExamRequest.
     */
    public function updateExam(Exam $exam, array $data): Exam
    {
        if ($exam->is_published) {
            throw new ConflictException('Ujian yang sudah dipublikasikan tidak dapat diubah konfigurasinya.');
        }

        $exam->update($data);

        return $exam;
    }

    public function deleteExam(Exam $exam): void
    {
        if ($exam->attempts()->exists()) {
            throw new ConflictException('Ujian yang sudah memiliki percobaan peserta tidak dapat dihapus.');
        }

        $exam->delete();
    }

    /**
     * @param  array<string, mixed>  $data  Divalidasi StoreExamQuestionRequest — question_text, points, options[].
     */
    public function addQuestion(Exam $exam, array $data): ExamQuestion
    {
        if ($exam->is_published) {
            throw new ConflictException('Ujian yang sudah dipublikasikan tidak dapat diubah konfigurasinya.');
        }

        return DB::transaction(function () use ($exam, $data): ExamQuestion {
            $question = $exam->questions()->create([
                'question_text' => $data['question_text'],
                'points' => $data['points'] ?? 1,
                'order_index' => $data['order_index'] ?? $exam->questions()->count(),
                'is_selected' => $data['is_selected'] ?? true,
            ]);

            foreach ((array) $data['options'] as $index => $option) {
                $question->options()->create([
                    'option_text' => $option['option_text'],
                    'is_correct' => (bool) $option['is_correct'],
                    'order_index' => $index,
                ]);
            }

            return $question->load('options');
        });
    }

    /**
     * @param  array<string, mixed>  $data  Divalidasi UpdateExamQuestionRequest.
     */
    public function updateQuestion(ExamQuestion $question, array $data): ExamQuestion
    {
        if ($question->exam->is_published) {
            throw new ConflictException('Ujian yang sudah dipublikasikan tidak dapat diubah konfigurasinya.');
        }

        return DB::transaction(function () use ($question, $data): ExamQuestion {
            $question->update(array_filter([
                'question_text' => $data['question_text'] ?? null,
                'points' => $data['points'] ?? null,
                'order_index' => $data['order_index'] ?? null,
                'is_selected' => $data['is_selected'] ?? null,
            ], fn ($value) => $value !== null));

            if (isset($data['options'])) {
                $question->options()->delete();

                foreach ((array) $data['options'] as $index => $option) {
                    $question->options()->create([
                        'option_text' => $option['option_text'],
                        'is_correct' => (bool) $option['is_correct'],
                        'order_index' => $index,
                    ]);
                }
            }

            return $question->fresh('options');
        });
    }

    public function deleteQuestion(ExamQuestion $question): void
    {
        if ($question->exam->is_published) {
            throw new ConflictException('Ujian yang sudah dipublikasikan tidak dapat diubah konfigurasinya.');
        }

        $question->delete();
    }

    /**
     * Memvalidasi konfigurasi sebelum publikasi (spec §2/§8/§18) dan
     * menandai ujian sebagai siap diakses peserta.
     */
    public function publish(Exam $exam): Exam
    {
        $poolSize = $exam->questions()->count();

        if ($poolSize === 0) {
            throw new ConflictException('Question pool masih kosong. Tambahkan soal terlebih dahulu.');
        }

        if ($exam->questions_per_participant > $poolSize) {
            throw new ConflictException('Jumlah soal per peserta tidak boleh melebihi jumlah soal dalam question pool.');
        }

        if ($exam->question_selection_mode === QuestionSelectionMode::Manual) {
            $selectedCount = $exam->questions()->where('is_selected', true)->count();

            if ($selectedCount < $exam->questions_per_participant) {
                throw new ConflictException(sprintf(
                    'Soal belum mencukupi. Anda membutuhkan %d soal, tetapi baru memilih %d soal.',
                    $exam->questions_per_participant,
                    $selectedCount,
                ));
            }
        }

        $hasQuestionWithoutCorrectAnswer = $exam->questions()
            ->whereDoesntHave('options', fn ($query) => $query->where('is_correct', true))
            ->exists();

        if ($hasQuestionWithoutCorrectAnswer) {
            throw new ConflictException('Setiap soal harus memiliki tepat satu jawaban benar.');
        }

        $exam->update(['is_published' => true, 'published_at' => now()]);

        return $exam;
    }

    /**
     * Nomor percobaan yang harus dipakai kalau peserta memanggil "mulai
     * ujian" sekarang — melanjutkan percobaan in_progress yang sudah ada
     * (resume idempotent, refresh-safe) kalau ada, atau nomor berikutnya
     * kalau percobaan terakhir sudah Submitted (percobaan baru/retry).
     * Wajib dipakai alih-alih menghitung `count() + 1` secara naif, yang
     * akan salah membuat percobaan baru setiap kali peserta me-refresh
     * ujian yang sedang dikerjakan.
     */
    public function nextAttemptNumber(Exam $exam, KrsItem $krsItem): int
    {
        $latest = $krsItem->examAttempts()
            ->where('exam_id', $exam->id)
            ->orderByDesc('attempt_number')
            ->first();

        return match (true) {
            $latest === null => 1,
            $latest->status === ExamAttemptStatus::InProgress => $latest->attempt_number,
            default => $latest->attempt_number + 1,
        };
    }

    /**
     * Menugaskan (atau mengembalikan penugasan yang sudah ada — stabil
     * lintas refresh, spec §14) set soal + urutan soal + urutan opsi jawaban
     * untuk satu percobaan peserta (KrsItem), sesuai konfigurasi ujian.
     */
    public function startAttempt(Exam $exam, KrsItem $krsItem, int $attemptNumber = 1): ExamAttempt
    {
        if (! $exam->is_published) {
            throw new ConflictException('Ujian belum dipublikasikan.');
        }

        if ($krsItem->class_section_id !== $exam->class_section_id) {
            throw new ConflictException('Mahasiswa ini tidak terdaftar pada kelas untuk ujian ini.');
        }

        $existing = ExamAttempt::query()
            ->where('exam_id', $exam->id)
            ->where('krs_item_id', $krsItem->id)
            ->where('attempt_number', $attemptNumber)
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        if ($attemptNumber > $exam->max_attempts) {
            throw new ConflictException('Batas maksimum percobaan ujian sudah tercapai.');
        }

        $pool = $exam->questions()->with('options')->orderBy('order_index')->get();

        $selected = match ($exam->question_selection_mode) {
            QuestionSelectionMode::All => $pool,
            QuestionSelectionMode::Manual => $pool->where('is_selected', true)->take($exam->questions_per_participant),
            QuestionSelectionMode::Random => $pool->shuffle()->take($exam->questions_per_participant),
        };

        $orderedQuestions = $exam->randomize_questions
            ? $selected->shuffle()->values()
            : $selected->sortBy('order_index')->values();

        $optionOrder = [];

        foreach ($orderedQuestions as $question) {
            $orderedOptions = $exam->randomize_options
                ? $question->options->shuffle()->values()
                : $question->options->sortBy('order_index')->values();

            $optionOrder[$question->id] = $orderedOptions->pluck('id')->all();
        }

        return DB::transaction(fn (): ExamAttempt => ExamAttempt::query()->create([
            'exam_id' => $exam->id,
            'krs_item_id' => $krsItem->id,
            'attempt_number' => $attemptNumber,
            'status' => ExamAttemptStatus::InProgress,
            'question_order' => $orderedQuestions->pluck('id')->all(),
            'option_order' => $optionOrder,
            'started_at' => now(),
        ]));
    }

    public function answerAttempt(ExamAttempt $attempt, string $examQuestionId, ?string $examQuestionOptionId): ExamAttemptAnswer
    {
        if ($attempt->status === ExamAttemptStatus::Submitted) {
            throw new ConflictException('Ujian ini sudah dikumpulkan.');
        }

        if (! in_array($examQuestionId, $attempt->question_order, true)) {
            throw new ConflictException('Soal ini bukan bagian dari ujian yang ditugaskan pada peserta ini.');
        }

        if ($examQuestionOptionId !== null) {
            $validOptionIds = $attempt->option_order[$examQuestionId] ?? [];

            if (! in_array($examQuestionOptionId, $validOptionIds, true)) {
                throw new ConflictException('Pilihan jawaban tidak valid untuk soal ini.');
            }
        }

        return ExamAttemptAnswer::query()->updateOrCreate(
            ['exam_attempt_id' => $attempt->id, 'exam_question_id' => $examQuestionId],
            ['exam_question_option_id' => $examQuestionOptionId, 'answered_at' => now()],
        );
    }

    /**
     * Mengakhiri percobaan dan menghitung nilai otomatis (pilihan ganda)
     * berbasis proporsi poin soal yang dijawab benar.
     */
    public function submitAttempt(ExamAttempt $attempt): ExamAttempt
    {
        if ($attempt->status === ExamAttemptStatus::Submitted) {
            throw new ConflictException('Ujian ini sudah dikumpulkan sebelumnya.');
        }

        return $this->finalizeAttempt($attempt, now());
    }

    /**
     * Batas waktu efektif satu percobaan — mana yang lebih dulu antara
     * habisnya durasi pengerjaan (`started_at + duration_minutes`) dan
     * jadwal berakhir ujian (`exam.ends_at`), kalau dosen mengonfigurasinya.
     */
    public function attemptDeadline(ExamAttempt $attempt): CarbonInterface
    {
        $durationDeadline = $attempt->started_at->addMinutes($attempt->exam->duration_minutes);
        $examEndsAt = $attempt->exam->ends_at;

        return $examEndsAt !== null && $examEndsAt->lessThan($durationDeadline)
            ? $examEndsAt
            : $durationDeadline;
    }

    /**
     * Menutup & menskor percobaan yang statusnya masih in_progress tapi
     * sudah melewati batas waktunya — dipanggil lazy di setiap akses
     * self-service (answer/submit/show) sehingga backend tetap
     * satu-satunya sumber kebenaran waktu tanpa perlu scheduled job
     * terpisah. Idempotent terhadap percobaan yang sudah Submitted.
     */
    public function finalizeIfExpired(ExamAttempt $attempt): ExamAttempt
    {
        if ($attempt->status === ExamAttemptStatus::Submitted) {
            return $attempt;
        }

        $deadline = $this->attemptDeadline($attempt);

        if (now()->lessThanOrEqualTo($deadline)) {
            return $attempt;
        }

        return $this->finalizeAttempt($attempt, $deadline);
    }

    private function finalizeAttempt(ExamAttempt $attempt, CarbonInterface $submittedAt): ExamAttempt
    {
        $questions = ExamQuestion::query()
            ->whereIn('id', $attempt->question_order)
            ->with('options')
            ->get()
            ->keyBy('id');

        $answers = $attempt->answers()->get()->keyBy('exam_question_id');

        $totalPoints = 0.0;
        $earnedPoints = 0.0;

        foreach ($attempt->question_order as $questionId) {
            $question = $questions->get($questionId);

            if ($question === null) {
                continue;
            }

            $totalPoints += (float) $question->points;

            $selectedOptionId = $answers->get($questionId)?->exam_question_option_id;

            if ($selectedOptionId === null) {
                continue;
            }

            $correctOption = $question->options->firstWhere('is_correct', true);

            if ($correctOption !== null && $correctOption->id === $selectedOptionId) {
                $earnedPoints += (float) $question->points;
            }
        }

        $rawScore = $totalPoints > 0 ? round(($earnedPoints / $totalPoints) * 100, 2) : 0.0;

        // Penalti sudah terakumulasi real-time tiap kali recordViolation()
        // dipanggil selama ujian berlangsung — di sini hanya dipakai untuk
        // menurunkan final score, bukan dihitung ulang dari awal (spec §4/§12).
        $penaltyScore = (float) $attempt->penalty_score;
        $finalScore = max(0.0, round($rawScore - $penaltyScore, 2));
        $grade = $this->resolveGrade($attempt->exam, $finalScore);
        $weightedScore = $attempt->exam->weight_percentage !== null
            ? round($finalScore * (float) $attempt->exam->weight_percentage / 100, 2)
            : null;

        $attempt->update([
            'status' => ExamAttemptStatus::Submitted,
            'submitted_at' => $submittedAt,
            'raw_score' => $rawScore,
            'score' => $finalScore,
            'grade' => $grade,
            'weighted_score' => $weightedScore,
        ]);

        return $attempt;
    }

    /**
     * Huruf nilai untuk satu skor akhir — pakai rentang yang dikonfigurasi
     * dosen untuk ujian ini kalau ada (spec §8), jatuh kembali ke ambang
     * batas standar LetterGrade::fromScore() kalau belum dikonfigurasi
     * supaya tetap ada nilai yang masuk akal tanpa setup tambahan.
     */
    public function resolveGrade(Exam $exam, float $finalScore): ?string
    {
        $ranges = $exam->relationLoaded('gradeRanges') ? $exam->gradeRanges : $exam->gradeRanges()->get();

        if ($ranges->isEmpty()) {
            return LetterGrade::fromScore($finalScore)->value;
        }

        $matched = $ranges->first(
            fn (ExamGradeRange $range) => $finalScore >= (float) $range->min_score && $finalScore <= (float) $range->max_score,
        );

        return $matched?->grade;
    }

    /**
     * Mengganti seluruh rentang nilai ujian ini sekaligus (spec §8) — replace-all
     * di dalam transaksi, sama seperti pola updateQuestion() mengganti opsi.
     * Validasi non-overlap/non-duplikat/batas 0-100 dilakukan di
     * UpsertExamGradeRangesRequest sebelum sampai ke sini.
     *
     * @param  array<int, array{grade: string, min_score: float, max_score: float}>  $ranges
     * @return Collection<int, ExamGradeRange>
     */
    public function upsertGradeRanges(Exam $exam, array $ranges): Collection
    {
        return DB::transaction(function () use ($exam, $ranges): Collection {
            $exam->gradeRanges()->delete();

            foreach ($ranges as $range) {
                $exam->gradeRanges()->create([
                    'grade' => $range['grade'],
                    'min_score' => $range['min_score'],
                    'max_score' => $range['max_score'],
                ]);
            }

            return $exam->gradeRanges()->orderBy('min_score')->get();
        });
    }

    /**
     * Mencatat satu pelanggaran (spec §4) dan langsung menambah
     * `penalty_score` attempt (bukan dihitung ulang tiap kali, supaya
     * lecturer bisa memantau penalti berjalan sebelum peserta submit).
     * Ditolak kalau attempt sudah Submitted — pelanggaran setelah ujian
     * berakhir tidak relevan lagi.
     */
    public function recordViolation(ExamAttempt $attempt, ExamViolationType $type, ?array $metadata = null): ExamViolation
    {
        if ($attempt->status === ExamAttemptStatus::Submitted) {
            throw new ConflictException('Ujian ini sudah dikumpulkan, pelanggaran tidak lagi dicatat.');
        }

        return DB::transaction(function () use ($attempt, $type, $metadata): ExamViolation {
            $sequenceNumber = $attempt->violations()->count() + 1;
            $penaltyPoints = $type->penaltyPoints();

            $violation = $attempt->violations()->create([
                'violation_type' => $type,
                'sequence_number' => $sequenceNumber,
                'penalty_points' => $penaltyPoints,
                'occurred_at' => now(),
                'metadata' => $metadata,
            ]);

            $attempt->increment('penalty_score', $penaltyPoints);

            return $violation;
        });
    }

    /**
     * @return Collection<int, ExamViolation>
     */
    public function violationsForAttempt(ExamAttempt $attempt): Collection
    {
        return $attempt->violations()->orderBy('sequence_number')->get();
    }

    /**
     * Pelanggaran lintas semua peserta ujian ini sejak `$since` (spec §6) —
     * dipakai endpoint polling yang di-poll berkala oleh halaman Detail
     * Ujian dosen. Diurutkan terlama dulu supaya klien bisa memakai waktu
     * kejadian terakhir sebagai cursor `since` panggilan berikutnya.
     *
     * @return Collection<int, ExamViolation>
     */
    public function recentViolationsForExam(Exam $exam, ?CarbonInterface $since): Collection
    {
        return ExamViolation::query()
            ->whereHas('examAttempt', fn ($query) => $query->where('exam_id', $exam->id))
            ->with('examAttempt.krsItem.student')
            ->when($since !== null, fn ($query) => $query->where('occurred_at', '>', $since))
            ->orderBy('occurred_at')
            ->get();
    }

    /**
     * Data lengkap hasil + koreksi satu percobaan (dipakai endpoint hasil
     * ujian JSON dan PDF) — backend satu-satunya sumber kebenaran skor dan
     * kunci jawaban, dihitung ulang dari jawaban tersimpan (bukan dipercaya
     * dari klien). TIDAK PERNAH dipanggil untuk attempt yang belum
     * Submitted — pemanggilnya (StudentExamController::result()/resultPdf())
     * wajib menolak sebelum sampai ke sini (spec §6).
     *
     * @return array{
     *     attempt: array{id: string, attempt_number: int, started_at: string, submitted_at: string|null, duration_seconds: int|null},
     *     student: array{name: string, nim: string},
     *     exam: array{title: string, course_name: string|null},
     *     summary: array{total_questions: int, correct_answers: int, wrong_answers: int, score: string, percentage: string},
     *     questions: array<int, array{number: int, question_text: string|null, options: array<int, array{id: string, option_text: string|null}>, selected_option_id: string|null, correct_option_id: string|null, is_correct: bool, explanation: string|null}>,
     * }
     */
    public function buildAttemptResult(ExamAttempt $attempt): array
    {
        $attempt->loadMissing(['krsItem.student', 'exam.classSection.course', 'answers', 'violations']);

        $questionsById = ExamQuestion::query()
            ->whereIn('id', $attempt->question_order)
            ->with('options')
            ->get()
            ->keyBy('id');

        $answersByQuestionId = $attempt->answers->keyBy('exam_question_id');
        $correctAnswers = 0;

        $questions = collect($attempt->question_order)->values()->map(function (string $questionId, int $index) use ($attempt, $questionsById, $answersByQuestionId, &$correctAnswers) {
            $question = $questionsById->get($questionId);
            $optionsById = $question?->options->keyBy('id');
            $orderedOptionIds = $attempt->option_order[$questionId] ?? [];

            $selectedOptionId = $answersByQuestionId->get($questionId)?->exam_question_option_id;
            $correctOption = $question?->options->firstWhere('is_correct', true);
            $isCorrect = $selectedOptionId !== null && $correctOption !== null && $selectedOptionId === $correctOption->id;

            if ($isCorrect) {
                $correctAnswers++;
            }

            return [
                'number' => $index + 1,
                'question_text' => $question?->question_text,
                'options' => collect($orderedOptionIds)->map(fn (string $optionId) => [
                    'id' => $optionId,
                    'option_text' => $optionsById?->get($optionId)?->option_text,
                ])->values()->all(),
                'selected_option_id' => $selectedOptionId,
                'correct_option_id' => $correctOption?->id,
                'is_correct' => $isCorrect,
                'explanation' => null,
            ];
        })->values()->all();

        $totalQuestions = count($attempt->question_order);
        $startedAt = $attempt->started_at;
        $submittedAt = $attempt->submitted_at;
        $score = (string) $attempt->score;

        return [
            'attempt' => [
                'id' => $attempt->id,
                'attempt_number' => $attempt->attempt_number,
                'started_at' => $startedAt->toIso8601String(),
                'submitted_at' => $submittedAt?->toIso8601String(),
                'duration_seconds' => $submittedAt !== null ? (int) $submittedAt->diffInSeconds($startedAt) : null,
            ],
            'student' => [
                'name' => $attempt->krsItem->student->name,
                'nim' => $attempt->krsItem->student->nim,
            ],
            'exam' => [
                'title' => $attempt->exam->title,
                'course_name' => $attempt->exam->classSection->course->name,
            ],
            'summary' => [
                'total_questions' => $totalQuestions,
                'correct_answers' => $correctAnswers,
                'wrong_answers' => $totalQuestions - $correctAnswers,
                'score' => $score,
                'percentage' => $score,
                // Rincian pipeline skor (spec §12) — raw_score dari jawaban
                // benar saja, penalty_score dari pelanggaran, score di atas
                // sudah final (raw - penalty, floor 0). Selalu tampil (bukan
                // cuma saat ada pelanggaran) supaya mahasiswa & dosen bisa
                // mengaudit cara nilai dihitung.
                'raw_score' => (string) $attempt->raw_score,
                'penalty_score' => (string) $attempt->penalty_score,
                'violation_count' => $attempt->violations->count(),
                'grade' => $attempt->grade,
                'weighted_score' => $attempt->weighted_score !== null ? (string) $attempt->weighted_score : null,
            ],
            'questions' => $questions,
        ];
    }

    /**
     * Query dasar "peserta terdaftar untuk ujian ini" (KrsItem Enrolled di
     * class_section ujian, dengan attempt terbarunya) — satu sumber
     * kebenaran dipakai bersama oleh ExamAttemptController::index() (tabel
     * peserta) dan ExamController::recap()/exportRecap() (Score Recap,
     * spec §7), supaya keduanya tidak bisa saling menyimpang.
     */
    public function participantsQuery(Exam $exam): \Illuminate\Database\Eloquent\Builder
    {
        return KrsItem::query()
            ->where('class_section_id', $exam->class_section_id)
            ->where('status', KrsItemStatus::Enrolled)
            ->with([
                'student',
                'examAttempts' => fn ($query) => $query->where('exam_id', $exam->id)->latest('attempt_number')->withCount('violations'),
            ]);
    }

    /**
     * KrsItem milik `$student` yang berhak atas `$exam` — mahasiswa berhak
     * kalau ia terdaftar aktif (`Enrolled`) di class_section ujian ini.
     * Satu-satunya jalur resolusi KrsItem untuk endpoint self-service —
     * tidak pernah dipercayakan ke input klien (lihat StudentExamController).
     */
    public function resolveEligibleKrsItem(Exam $exam, Student $student): KrsItem
    {
        $krsItem = KrsItem::query()
            ->where('student_id', $student->id)
            ->where('class_section_id', $exam->class_section_id)
            ->where('status', KrsItemStatus::Enrolled)
            ->first();

        if ($krsItem === null) {
            throw new ConflictException('Anda tidak terdaftar pada kelas untuk ujian ini.');
        }

        return $krsItem;
    }

    /**
     * Status ujian dari sudut pandang satu peserta (spec §2) — dibedakan
     * dari `exams.is_published`, yang hanya menyatakan ujian sudah bisa
     * diakses peserta yang berhak, bukan status pengerjaan individual.
     *
     * @return 'upcoming'|'available'|'in_progress'|'completed'|'expired'
     */
    public function computeStudentStatus(Exam $exam, ?ExamAttempt $latestAttempt): string
    {
        if ($latestAttempt !== null) {
            if ($latestAttempt->status === ExamAttemptStatus::InProgress) {
                return 'in_progress';
            }

            $attemptsUsed = $latestAttempt->attempt_number;
            $canRetake = $attemptsUsed < $exam->max_attempts
                && ($exam->ends_at === null || now()->lessThanOrEqualTo($exam->ends_at));

            if (! $canRetake) {
                return 'completed';
            }
        }

        if ($exam->starts_at !== null && now()->lessThan($exam->starts_at)) {
            return 'upcoming';
        }

        if ($exam->ends_at !== null && now()->greaterThan($exam->ends_at)) {
            return $latestAttempt !== null ? 'completed' : 'expired';
        }

        return 'available';
    }

    /**
     * Jendela waktu ujian ini boleh dikerjakan — dipakai bersama oleh
     * self-service login (StudentExamController::start(), pesan default)
     * dan akses publik lewat NIM (startPublicAttempt(), pesan di-override
     * sesuai spec §4) supaya keduanya tidak bisa saling menyimpang aturan.
     */
    public function assertWithinSchedule(Exam $exam, ?string $notStartedMessage = null, string $endedMessage = 'Ujian sudah berakhir.'): void
    {
        if ($exam->starts_at !== null && now()->lessThan($exam->starts_at)) {
            throw new ConflictException($notStartedMessage ?? sprintf(
                'Ujian belum dapat dikerjakan — jadwal dimulai pada %s.',
                $exam->starts_at->toIso8601String(),
            ));
        }

        if ($exam->ends_at !== null && now()->greaterThan($exam->ends_at)) {
            throw new ConflictException($endedMessage);
        }
    }

    /**
     * Menolak akses ke hasil/koreksi kalau percobaan belum Submitted atau
     * dosen belum mengizinkan hasil terlihat — dipakai bersama oleh
     * StudentExamController dan PublicExamController supaya kunci jawaban
     * tidak pernah bocor lebih awal dari jalur manapun (spec §6).
     */
    public function guardResultAvailable(ExamAttempt $attempt): void
    {
        if ($attempt->status !== ExamAttemptStatus::Submitted) {
            throw new ConflictException('Ujian belum dikumpulkan, hasil belum tersedia.');
        }

        if (! $attempt->exam->show_result_after_submission) {
            throw new ConflictException('Hasil ujian belum tersedia. Menunggu dipublikasikan oleh dosen.');
        }
    }

    /**
     * Token opaque baru untuk URL akses publik ujian (spec §1-2) —
     * dipanggil untuk "Generate" (belum ada token) maupun "Regenerate"
     * (token lama langsung tidak valid karena diganti, bukan disimpan
     * sebagai riwayat).
     */
    public function generateAccessToken(Exam $exam): Exam
    {
        $exam->update([
            'access_token' => Str::random(40),
            'access_token_generated_at' => now(),
        ]);

        return $exam;
    }

    public function findByAccessToken(string $token): Exam
    {
        return Exam::query()->where('access_token', $token)->firstOrFail();
    }

    public function resolveStudentByNim(string $nim): Student
    {
        $student = Student::query()->where('nim', $nim)->first();

        if ($student === null) {
            throw new ConflictException('NIM mahasiswa tidak ditemukan.');
        }

        return $student;
    }

    /**
     * Orkestrasi akses ujian publik lewat NIM (spec §4-5) — mengomposisikan
     * potongan yang sudah ada (resolveEligibleKrsItem/assertWithinSchedule/
     * computeStudentStatus/startAttempt) dengan pesan Indonesia persis
     * seperti spec, tanpa mengubah teks/pesan jalur self-service login yang
     * sudah berjalan (StudentExamController::start()).
     *
     * @return array{attempt: ExamAttempt, session_token: string}
     */
    public function startPublicAttempt(Exam $exam, Student $student): array
    {
        if (! $exam->is_published) {
            throw new ConflictException('Ujian belum tersedia.');
        }

        try {
            $krsItem = $this->resolveEligibleKrsItem($exam, $student);
        } catch (ConflictException) {
            throw new ConflictException('NIM mahasiswa tidak ditemukan.');
        }

        $this->assertWithinSchedule($exam, 'Ujian belum tersedia.', 'Ujian telah berakhir.');

        $latest = $krsItem->examAttempts()->where('exam_id', $exam->id)->orderByDesc('attempt_number')->first();

        if ($latest !== null) {
            $latest = $this->finalizeIfExpired($latest);
        }

        if ($this->computeStudentStatus($exam, $latest) === 'completed') {
            throw new ConflictException('Anda sudah menyelesaikan ujian ini.');
        }

        $attemptNumber = $this->nextAttemptNumber($exam, $krsItem);
        $attempt = $this->finalizeIfExpired($this->startAttempt($exam, $krsItem, $attemptNumber));

        $sessionToken = Str::random(60);
        $attempt->update([
            'session_token_hash' => hash('sha256', $sessionToken),
            // Grace period setelah deadline supaya link/QR yang sama masih
            // bisa dipakai membuka halaman hasil & download PDF (spec §9-11)
            // tanpa token tetap hidup tanpa batas waktu (spec §12).
            'session_expires_at' => $this->attemptDeadline($attempt)->addHours(24),
        ]);

        return ['attempt' => $attempt->fresh()->load('answers'), 'session_token' => $sessionToken];
    }

    public function findAttemptBySessionToken(string $token): ExamAttempt
    {
        return ExamAttempt::query()
            ->where('session_token_hash', hash('sha256', $token))
            ->where('session_expires_at', '>=', now())
            ->firstOrFail();
    }
}
