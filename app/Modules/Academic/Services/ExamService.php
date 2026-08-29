<?php

namespace Modules\Academic\Services;

use App\Support\Http\Exceptions\ConflictException;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Academic\Enums\ExamAttemptStatus;
use Modules\Academic\Enums\KrsItemStatus;
use Modules\Academic\Enums\QuestionSelectionMode;
use Modules\Academic\Models\ClassSection;
use Modules\Academic\Models\Exam;
use Modules\Academic\Models\ExamAttempt;
use Modules\Academic\Models\ExamAttemptAnswer;
use Modules\Academic\Models\ExamQuestion;
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

        $score = $totalPoints > 0 ? round(($earnedPoints / $totalPoints) * 100, 2) : 0.0;

        $attempt->update([
            'status' => ExamAttemptStatus::Submitted,
            'submitted_at' => $submittedAt,
            'score' => $score,
        ]);

        return $attempt;
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
}
