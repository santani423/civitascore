<?php

namespace Modules\Academic\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Http\ApiResponse;
use App\Support\Http\Exceptions\ConflictException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Modules\Academic\Enums\ExamAttemptStatus;
use Modules\Academic\Enums\KrsItemStatus;
use Modules\Academic\Models\Exam;
use Modules\Academic\Models\ExamAttempt;
use Modules\Academic\Models\KrsItem;
use Modules\Academic\Models\Student;
use Modules\Academic\Policies\ExamParticipationPolicy;
use Modules\Academic\Requests\AnswerExamAttemptRequest;
use Modules\Academic\Resources\StudentExamAttemptResource;
use Modules\Academic\Resources\StudentExamResource;
use Modules\Academic\Services\ExamService;

/**
 * Portal Mahasiswa — self-service mengerjakan ujian sendiri. Terpisah dari
 * ExamAttemptController (dosen/pengawas merekam atas nama KrsItem manapun,
 * lihat komentar di sana): controller ini tidak pernah menerima KrsItem dari
 * input klien — selalu diresolusi dari identitas Student milik user yang
 * login (`$user->student`), lalu di-cross-check ulang di object level lewat
 * ExamParticipationPolicy sebelum memproses apapun (defense in depth
 * terhadap manipulasi ID, spec §7).
 */
class StudentExamController extends Controller
{
    public function __construct(
        private readonly ExamService $exams,
        private readonly ExamParticipationPolicy $policy,
    ) {}

    public function index(): JsonResponse
    {
        $user = Auth::user();
        $student = $this->requireStudent($user);

        abort_unless($this->policy->viewOwn($user), 403);

        $exams = Exam::query()
            ->where('is_published', true)
            ->whereHas(
                'classSection.krsItems',
                fn ($query) => $query->where('student_id', $student->id)->where('status', KrsItemStatus::Enrolled),
            )
            ->with(['classSection.course', 'creator'])
            ->latest('published_at')
            ->get();

        $krsItemIds = KrsItem::query()
            ->where('student_id', $student->id)
            ->where('status', KrsItemStatus::Enrolled)
            ->pluck('id');

        $latestAttemptsByExam = ExamAttempt::query()
            ->whereIn('exam_id', $exams->pluck('id'))
            ->whereIn('krs_item_id', $krsItemIds)
            ->orderByDesc('attempt_number')
            ->get()
            ->groupBy('exam_id')
            ->map(fn ($attempts) => $attempts->first());

        $data = $exams->map(function (Exam $exam) use ($latestAttemptsByExam) {
            $latest = $latestAttemptsByExam->get($exam->id);

            if ($latest !== null) {
                $latest = $this->exams->finalizeIfExpired($latest);
            }

            return new StudentExamResource($exam, $this->buildStudentContext($exam, $latest));
        });

        return ApiResponse::success($data->values());
    }

    public function show(Exam $exam): JsonResponse
    {
        $user = Auth::user();
        $student = $this->requireStudent($user);

        abort_unless($this->policy->viewOwn($user), 403);

        $krsItem = $this->exams->resolveEligibleKrsItem($exam, $student);
        abort_unless($exam->is_published, 404);

        $exam->loadMissing(['classSection.course', 'creator']);

        $latest = $krsItem->examAttempts()
            ->where('exam_id', $exam->id)
            ->orderByDesc('attempt_number')
            ->first();

        if ($latest !== null) {
            $latest = $this->exams->finalizeIfExpired($latest);
        }

        return ApiResponse::success(new StudentExamResource($exam, $this->buildStudentContext($exam, $latest)));
    }

    public function start(Exam $exam): JsonResponse
    {
        $user = Auth::user();
        $student = $this->requireStudent($user);

        $krsItem = $this->exams->resolveEligibleKrsItem($exam, $student);
        abort_unless($this->policy->startOwn($user, $krsItem), 403);

        if ($exam->starts_at !== null && now()->lessThan($exam->starts_at)) {
            throw new ConflictException(sprintf(
                'Ujian belum dapat dikerjakan — jadwal dimulai pada %s.',
                $exam->starts_at->toIso8601String(),
            ));
        }

        if ($exam->ends_at !== null && now()->greaterThan($exam->ends_at)) {
            throw new ConflictException('Ujian sudah berakhir.');
        }

        $attemptNumber = $this->exams->nextAttemptNumber($exam, $krsItem);
        $attempt = $this->exams->startAttempt($exam, $krsItem, $attemptNumber);
        $attempt = $this->exams->finalizeIfExpired($attempt);

        return ApiResponse::success(
            new StudentExamAttemptResource($attempt->fresh()->load('answers'), $this->isResultVisible($exam, $attempt)),
            'Ujian dimulai.',
        );
    }

    public function answer(AnswerExamAttemptRequest $request, ExamAttempt $examAttempt): JsonResponse
    {
        $user = Auth::user();
        $this->requireStudent($user);
        abort_unless($this->policy->recordOwn($user, $examAttempt), 403);

        $examAttempt = $this->exams->finalizeIfExpired($examAttempt);

        if ($examAttempt->status === ExamAttemptStatus::Submitted) {
            throw new ConflictException('Waktu ujian sudah habis, jawaban tidak bisa disimpan lagi.');
        }

        $this->exams->answerAttempt(
            $examAttempt,
            (string) $request->validated('exam_question_id'),
            $request->validated('exam_question_option_id'),
        );

        return ApiResponse::success(
            new StudentExamAttemptResource(
                $examAttempt->fresh()->load('answers'),
                $this->isResultVisible($examAttempt->exam, $examAttempt),
            ),
            'Jawaban tersimpan.',
        );
    }

    public function submit(ExamAttempt $examAttempt): JsonResponse
    {
        $user = Auth::user();
        $this->requireStudent($user);
        abort_unless($this->policy->recordOwn($user, $examAttempt), 403);

        $examAttempt = $this->exams->finalizeIfExpired($examAttempt);

        if ($examAttempt->status !== ExamAttemptStatus::Submitted) {
            $examAttempt = $this->exams->submitAttempt($examAttempt);
        }

        return ApiResponse::success(
            new StudentExamAttemptResource(
                $examAttempt->load('answers'),
                $this->isResultVisible($examAttempt->exam, $examAttempt),
            ),
            'Ujian berhasil dikumpulkan.',
        );
    }

    public function showAttempt(ExamAttempt $examAttempt): JsonResponse
    {
        $user = Auth::user();
        $this->requireStudent($user);
        abort_unless($this->policy->recordOwn($user, $examAttempt), 403);

        $examAttempt = $this->exams->finalizeIfExpired($examAttempt);

        return ApiResponse::success(new StudentExamAttemptResource(
            $examAttempt->load('answers'),
            $this->isResultVisible($examAttempt->exam, $examAttempt),
        ));
    }

    private function requireStudent(?User $user): Student
    {
        abort_unless($user !== null && $user->student !== null, 403, 'Akun ini tidak tertaut ke data mahasiswa.');

        return $user->student;
    }

    private function isResultVisible(Exam $exam, ExamAttempt $attempt): bool
    {
        return $exam->show_result_after_submission && $attempt->status === ExamAttemptStatus::Submitted;
    }

    /**
     * @return array{attempts_used: int, status: string, result_visible: bool, score: string|null, latest_attempt_id: string|null}
     */
    private function buildStudentContext(Exam $exam, ?ExamAttempt $latestAttempt): array
    {
        $status = $this->exams->computeStudentStatus($exam, $latestAttempt);
        $resultVisible = $latestAttempt !== null && $this->isResultVisible($exam, $latestAttempt);

        return [
            'attempts_used' => $latestAttempt?->attempt_number ?? 0,
            'status' => $status,
            'result_visible' => $resultVisible,
            'score' => $resultVisible ? $latestAttempt?->score : null,
            'latest_attempt_id' => $latestAttempt?->id,
        ];
    }
}
