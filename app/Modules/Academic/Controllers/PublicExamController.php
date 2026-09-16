<?php

namespace Modules\Academic\Controllers;

use App\Http\Controllers\Controller;
use App\Support\Http\ApiResponse;
use App\Support\Http\Exceptions\ConflictException;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Modules\Academic\Enums\ExamAttemptStatus;
use Modules\Academic\Enums\ExamViolationType;
use Modules\Academic\Models\ExamAttempt;
use Modules\Academic\Requests\AnswerExamAttemptRequest;
use Modules\Academic\Requests\RecordExamViolationRequest;
use Modules\Academic\Resources\PublicExamResource;
use Modules\Academic\Resources\StudentExamAttemptResource;
use Modules\Academic\Services\ExamService;

/**
 * Akses ujian publik lewat link/QR + NIM, tanpa login (spec §3-11). Terpisah
 * dari StudentExamController (self-service login Sanctum): identitas di sini
 * tidak pernah datang dari sesi login, melainkan diresolusi murni dari token
 * di URL — `access_token` mengidentifikasi ujian, `session_token` (hasil
 * validasi NIM) mengidentifikasi percobaan — tidak pernah dari input klien
 * seperti student_id/krs_item_id (spec §12).
 */
class PublicExamController extends Controller
{
    public function __construct(private readonly ExamService $exams) {}

    public function show(string $accessToken): JsonResponse
    {
        $exam = $this->exams->findByAccessToken($accessToken)->loadMissing(['classSection.course', 'creator']);

        return ApiResponse::success(new PublicExamResource($exam));
    }

    public function access(Request $request, string $accessToken): JsonResponse
    {
        $data = $request->validate(['nim' => ['required', 'string']]);

        $exam = $this->exams->findByAccessToken($accessToken);
        $student = $this->exams->resolveStudentByNim($data['nim']);
        $result = $this->exams->startPublicAttempt($exam, $student);

        return ApiResponse::success(
            ['session_token' => $result['session_token']],
            'Ujian dimulai.',
        );
    }

    public function showAttempt(string $sessionToken): JsonResponse
    {
        $attempt = $this->exams->finalizeIfExpired($this->exams->findAttemptBySessionToken($sessionToken));
        $attempt->loadMissing('krsItem.student');

        // Halaman ujian publik tidak punya sesi login untuk menunjukkan
        // identitas peserta (spec §6 "Mahasiswa: NIM/Nama") — disisipkan di
        // sini saja (bukan di StudentExamAttemptResource, dipakai bersama
        // dengan jalur login) supaya tidak mengubah bentuk resource itu.
        $data = (new StudentExamAttemptResource($attempt->load(['answers', 'violations']), $this->isResultVisible($attempt)))->resolve();
        $data['student'] = [
            'name' => $attempt->krsItem->student->name,
            'nim' => $attempt->krsItem->student->nim,
        ];

        return ApiResponse::success($data);
    }

    public function answer(AnswerExamAttemptRequest $request, string $sessionToken): JsonResponse
    {
        $attempt = $this->exams->finalizeIfExpired($this->exams->findAttemptBySessionToken($sessionToken));

        if ($attempt->status === ExamAttemptStatus::Submitted) {
            throw new ConflictException('Waktu ujian sudah habis, jawaban tidak bisa disimpan lagi.');
        }

        $this->exams->answerAttempt(
            $attempt,
            (string) $request->validated('exam_question_id'),
            $request->validated('exam_question_option_id'),
        );

        return ApiResponse::success(
            new StudentExamAttemptResource($attempt->fresh()->load(['answers', 'violations']), $this->isResultVisible($attempt)),
            'Jawaban tersimpan.',
        );
    }

    public function recordViolation(RecordExamViolationRequest $request, string $sessionToken): JsonResponse
    {
        $attempt = $this->exams->finalizeIfExpired($this->exams->findAttemptBySessionToken($sessionToken));
        $this->exams->recordViolation(
            $attempt,
            ExamViolationType::from($request->validated('violation_type')),
            $request->validated('metadata'),
        );

        return ApiResponse::success(
            new StudentExamAttemptResource($attempt->fresh()->load(['answers', 'violations']), $this->isResultVisible($attempt)),
            'Pelanggaran tercatat.',
        );
    }

    public function submit(string $sessionToken): JsonResponse
    {
        $attempt = $this->exams->finalizeIfExpired($this->exams->findAttemptBySessionToken($sessionToken));

        if ($attempt->status !== ExamAttemptStatus::Submitted) {
            $attempt = $this->exams->submitAttempt($attempt);
        }

        return ApiResponse::success(
            new StudentExamAttemptResource($attempt->load(['answers', 'violations']), $this->isResultVisible($attempt)),
            'Ujian berhasil dikumpulkan.',
        );
    }

    public function result(string $sessionToken): JsonResponse
    {
        $attempt = $this->exams->finalizeIfExpired($this->exams->findAttemptBySessionToken($sessionToken));
        $this->exams->guardResultAvailable($attempt);

        return ApiResponse::success($this->exams->buildAttemptResult($attempt));
    }

    public function resultPdf(string $sessionToken): Response
    {
        $attempt = $this->exams->finalizeIfExpired($this->exams->findAttemptBySessionToken($sessionToken));
        $this->exams->guardResultAvailable($attempt);

        $result = $this->exams->buildAttemptResult($attempt);
        $filename = 'hasil-ujian-'.Str::slug($result['exam']['title']).'-'.$attempt->id.'.pdf';

        return Pdf::loadView('exams.result-pdf', ['result' => $result])->download($filename);
    }

    private function isResultVisible(ExamAttempt $attempt): bool
    {
        return $attempt->exam->show_result_after_submission && $attempt->status === ExamAttemptStatus::Submitted;
    }
}
