<?php

namespace Modules\Academic\Controllers;

use App\Http\Controllers\Controller;
use App\Support\Http\ApiResponse;
use App\Support\Http\ListQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Academic\Enums\KrsItemStatus;
use Modules\Academic\Models\Exam;
use Modules\Academic\Models\ExamAttempt;
use Modules\Academic\Models\KrsItem;
use Modules\Academic\Requests\AnswerExamAttemptRequest;
use Modules\Academic\Resources\ExamAttemptResource;
use Modules\Academic\Resources\ExamParticipantResource;
use Modules\Academic\Services\ExamService;

/**
 * Direkam oleh pengguna berizin (dosen/pengawas) atas nama peserta (KrsItem)
 * — bukan self-service oleh mahasiswa, karena identity link Student→User
 * belum ada di aplikasi ini (lihat catatan di ExamService/rencana).
 */
class ExamAttemptController extends Controller
{
    public function __construct(private readonly ExamService $exams) {}

    /** Status keikutsertaan (belum/sedang/sudah mengerjakan) tiap peserta terdaftar di kelas ujian ini. */
    public function index(Request $request, Exam $exam): JsonResponse
    {
        $this->authorize('viewAny', ExamAttempt::class);

        $query = KrsItem::query()
            ->where('class_section_id', $exam->class_section_id)
            ->where('status', KrsItemStatus::Enrolled)
            ->with([
                'student',
                'examAttempts' => fn ($query) => $query->where('exam_id', $exam->id)->latest('attempt_number'),
            ])
            ->oldest();

        $paginator = ListQuery::paginate(query: $query, request: $request);

        return ApiResponse::paginated(ExamParticipantResource::collection($paginator));
    }

    public function start(KrsItem $krsItem, Exam $exam): JsonResponse
    {
        $this->authorize('record', ExamAttempt::class);

        $attempt = $this->exams->startAttempt($exam, $krsItem);

        return ApiResponse::success(new ExamAttemptResource($attempt->load('answers')), 'Ujian dimulai.');
    }

    public function answer(AnswerExamAttemptRequest $request, ExamAttempt $examAttempt): JsonResponse
    {
        $this->authorize('record', ExamAttempt::class);

        $this->exams->answerAttempt(
            $examAttempt,
            (string) $request->validated('exam_question_id'),
            $request->validated('exam_question_option_id'),
        );

        return ApiResponse::success(new ExamAttemptResource($examAttempt->fresh()->load('answers')), 'Jawaban tersimpan.');
    }

    public function submit(ExamAttempt $examAttempt): JsonResponse
    {
        $this->authorize('record', ExamAttempt::class);

        $attempt = $this->exams->submitAttempt($examAttempt);

        return ApiResponse::success(new ExamAttemptResource($attempt->load('answers')), 'Ujian berhasil dikumpulkan.');
    }
}
