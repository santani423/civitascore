<?php

namespace Modules\Academic\Controllers;

use App\Http\Controllers\Controller;
use App\Support\Http\ApiResponse;
use App\Support\Http\ListQuery;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Modules\Academic\Models\Exam;
use Modules\Academic\Models\ExamAttempt;
use Modules\Academic\Models\KrsItem;
use Modules\Academic\Requests\AnswerExamAttemptRequest;
use Modules\Academic\Resources\ExamAttemptResource;
use Modules\Academic\Resources\ExamParticipantResource;
use Modules\Academic\Resources\ExamViolationResource;
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
        $this->authorize('viewAny', [ExamAttempt::class, $exam]);

        $query = $this->exams->participantsQuery($exam)->oldest();

        $paginator = ListQuery::paginate(query: $query, request: $request);

        return ApiResponse::paginated(ExamParticipantResource::collection($paginator));
    }

    public function start(KrsItem $krsItem, Exam $exam): JsonResponse
    {
        $this->authorize('record', [ExamAttempt::class, $exam]);

        $attempt = $this->exams->startAttempt($exam, $krsItem);

        return ApiResponse::success(new ExamAttemptResource($attempt->load(['answers', 'violations'])), 'Ujian dimulai.');
    }

    public function answer(AnswerExamAttemptRequest $request, ExamAttempt $examAttempt): JsonResponse
    {
        $this->authorize('record', [ExamAttempt::class, $examAttempt->exam]);

        $this->exams->answerAttempt(
            $examAttempt,
            (string) $request->validated('exam_question_id'),
            $request->validated('exam_question_option_id'),
        );

        return ApiResponse::success(new ExamAttemptResource($examAttempt->fresh()->load(['answers', 'violations'])), 'Jawaban tersimpan.');
    }

    public function submit(ExamAttempt $examAttempt): JsonResponse
    {
        $this->authorize('record', [ExamAttempt::class, $examAttempt->exam]);

        $attempt = $this->exams->submitAttempt($examAttempt);

        return ApiResponse::success(new ExamAttemptResource($attempt->load(['answers', 'violations'])), 'Ujian berhasil dikumpulkan.');
    }

    /** Linimasa pelanggaran + ringkasan skor satu percobaan, dilihat dosen (spec §5). */
    public function violations(ExamAttempt $examAttempt): JsonResponse
    {
        $this->authorize('viewAny', [ExamAttempt::class, $examAttempt->exam]);

        $examAttempt->loadMissing('krsItem.student');
        $violations = $this->exams->violationsForAttempt($examAttempt);

        return ApiResponse::success([
            'student' => [
                'name' => $examAttempt->krsItem->student->name,
                'nim' => $examAttempt->krsItem->student->nim,
            ],
            'raw_score' => $examAttempt->raw_score,
            'penalty_score' => $examAttempt->penalty_score,
            'score' => $examAttempt->score,
            'grade' => $examAttempt->grade,
            'weighted_score' => $examAttempt->weighted_score,
            'violation_count' => $violations->count(),
            'violations' => ExamViolationResource::collection($violations),
        ]);
    }

    /**
     * Laporan lengkap satu percobaan untuk dosen — waktu pengerjaan, jawaban
     * per soal, pelanggaran, linimasa (lihat ExamService::buildAttemptReport).
     * finalizeIfExpired() dulu supaya attempt yang waktunya sudah habis tapi
     * belum pernah diakses lagi oleh peserta ikut terskor, sama seperti
     * endpoint hasil mahasiswa.
     */
    public function report(ExamAttempt $examAttempt): JsonResponse
    {
        $this->authorize('viewAny', [ExamAttempt::class, $examAttempt->exam]);

        $examAttempt = $this->exams->finalizeIfExpired($examAttempt);

        return ApiResponse::success($this->exams->buildAttemptReport($examAttempt));
    }

    public function reportPdf(ExamAttempt $examAttempt): Response
    {
        $this->authorize('viewAny', [ExamAttempt::class, $examAttempt->exam]);

        $examAttempt = $this->exams->finalizeIfExpired($examAttempt);
        $report = $this->exams->buildAttemptReport($examAttempt);
        $filename = 'laporan-ujian-'.Str::slug($report['exam']['title']).'-'.$report['student']['nim'].'-percobaan-'.$report['attempt']['attempt_number'].'.pdf';

        $pdf = Pdf::loadView('exams.attempt-report-pdf', ['report' => $report])->setPaper('a4');
        $pdf->render();
        $dompdf = $pdf->getDomPDF();
        $canvas = $dompdf->getCanvas();
        $canvas->page_text(
            $canvas->get_width() - 100,
            $canvas->get_height() - 29,
            'Halaman {PAGE_NUM} dari {PAGE_COUNT}',
            $dompdf->getFontMetrics()->getFont('helvetica'),
            6,
            [0.58, 0.64, 0.72],
        );

        return $pdf->download($filename);
    }
}
