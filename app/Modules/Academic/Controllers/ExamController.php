<?php

namespace Modules\Academic\Controllers;

use App\Http\Controllers\Controller;
use App\Support\Http\ApiResponse;
use App\Support\Http\ListQuery;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Modules\Academic\Enums\ExamAttemptStatus;
use Modules\Academic\Models\Exam;
use Modules\Academic\Models\ExamAttempt;
use Modules\Academic\Models\KrsItem;
use Modules\Academic\Requests\StoreExamRequest;
use Modules\Academic\Requests\UpdateExamRequest;
use Modules\Academic\Resources\ExamParticipantResource;
use Modules\Academic\Resources\ExamResource;
use Modules\Academic\Resources\ExamViolationResource;
use Modules\Academic\Services\ExamService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExamController extends Controller
{
    public function __construct(private readonly ExamService $exams) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Exam::class);

        $query = Exam::query()->with('classSection.course')->withCount('questions');

        $paginator = ListQuery::paginate(
            query: $query->latest(),
            request: $request,
            filterable: ['class_section_id', 'is_published'],
            sortable: ['created_at', 'title'],
        );

        return ApiResponse::paginated(ExamResource::collection($paginator));
    }

    public function store(StoreExamRequest $request): JsonResponse
    {
        $this->authorize('manage', Exam::class);

        $exam = $this->exams->createExam($request->validated());

        return ApiResponse::success(
            new ExamResource($exam->load('classSection.course')->loadCount('questions')),
            'Ujian berhasil dibuat.',
            status: 201,
        );
    }

    public function show(Exam $exam): JsonResponse
    {
        $this->authorize('viewAny', Exam::class);

        return ApiResponse::success(new ExamResource($exam->load('classSection.course')->loadCount('questions')));
    }

    public function update(UpdateExamRequest $request, Exam $exam): JsonResponse
    {
        $this->authorize('manage', Exam::class);

        $exam = $this->exams->updateExam($exam, $request->validated());

        return ApiResponse::success(
            new ExamResource($exam->load('classSection.course')->loadCount('questions')),
            'Ujian berhasil diperbarui.',
        );
    }

    public function destroy(Exam $exam): JsonResponse
    {
        $this->authorize('delete', Exam::class);

        $this->exams->deleteExam($exam);

        return ApiResponse::success(null, 'Ujian berhasil dihapus.');
    }

    public function publish(Exam $exam): JsonResponse
    {
        $this->authorize('publish', Exam::class);

        $exam = $this->exams->publish($exam);

        return ApiResponse::success(
            new ExamResource($exam->load('classSection.course')->loadCount('questions')),
            'Ujian berhasil dipublikasikan.',
        );
    }

    /**
     * Generate (belum ada token) atau regenerate (token lama langsung tidak
     * valid) link akses publik ujian — lihat ExamService::generateAccessToken().
     */
    public function generateAccessLink(Exam $exam): JsonResponse
    {
        $this->authorize('manage', Exam::class);

        $exam = $this->exams->generateAccessToken($exam);

        return ApiResponse::success(
            new ExamResource($exam->load('classSection.course')->loadCount('questions')),
            'Link akses ujian berhasil dibuat.',
        );
    }

    /**
     * Pelanggaran lintas semua peserta ujian ini sejak `?since=` (spec §6) —
     * di-poll berkala oleh halaman Detail Ujian dosen untuk alert
     * near-real-time. Lihat catatan di ExamService::recentViolationsForExam()
     * soal kenapa polling, bukan WebSocket/broadcasting.
     */
    public function recentViolations(Request $request, Exam $exam): JsonResponse
    {
        $this->authorize('viewAny', ExamAttempt::class);

        $since = $request->query('since') ? Carbon::parse((string) $request->query('since')) : null;
        $violations = $this->exams->recentViolationsForExam($exam, $since);

        return ApiResponse::success(ExamViolationResource::collection($violations));
    }

    /** Ringkasan + tabel nilai seluruh peserta ujian ini (spec §7). */
    public function recap(Exam $exam): JsonResponse
    {
        $this->authorize('viewAny', Exam::class);

        $exam->loadMissing(['classSection.course', 'classSection.academicTerm']);
        $participants = $this->exams->participantsQuery($exam)->oldest()->get();

        return ApiResponse::success([
            'summary' => $this->buildRecapSummary($exam, $participants),
            'participants' => ExamParticipantResource::collection($participants),
        ]);
    }

    public function exportRecap(Request $request, Exam $exam): StreamedResponse|Response
    {
        $this->authorize('viewAny', Exam::class);

        $exam->loadMissing(['classSection.course']);
        $participants = $this->exams->participantsQuery($exam)->oldest()->get();
        $format = $request->query('format', 'csv');

        return $format === 'pdf'
            ? $this->exportRecapPdf($exam, $participants)
            : $this->exportRecapCsv($exam, $participants);
    }

    /**
     * PDF naskah ujian untuk dosen (spec §10) — kunci jawaban HANYA
     * disertakan kalau pengguna punya hak kelola ujian (exams.create/update),
     * bukan sekadar exams.read, dan itu ditentukan di backend (Gate::allows),
     * tidak pernah dipercayakan ke parameter request `with_answers` begitu saja.
     */
    public function downloadPdf(Request $request, Exam $exam): Response
    {
        $this->authorize('viewAny', Exam::class);

        $withAnswers = $request->boolean('with_answers') && $request->user()->can('manage', Exam::class);

        $exam->loadMissing(['classSection.course', 'classSection.academicTerm', 'creator', 'questions.options']);
        $filename = 'naskah-ujian-'.Str::slug($exam->title).'.pdf';

        return Pdf::loadView('exams.exam-pdf', ['exam' => $exam, 'withAnswers' => $withAnswers])->download($filename);
    }

    /**
     * @param  Collection<int, KrsItem>  $participants
     * @return array<string, mixed>
     */
    private function buildRecapSummary(Exam $exam, Collection $participants): array
    {
        $latestAttempts = $participants->map(fn (KrsItem $p) => $p->examAttempts->first());
        $submitted = $latestAttempts->filter(fn (?ExamAttempt $a) => $a !== null && $a->status === ExamAttemptStatus::Submitted);
        $scores = $submitted->map(fn (ExamAttempt $a) => (float) $a->score);

        return [
            'exam_title' => $exam->title,
            'course_name' => $exam->classSection->course?->name,
            'class_code' => $exam->classSection->class_code,
            'semester_label' => $exam->classSection->academicTerm?->academic_year,
            'total_participants' => $participants->count(),
            'completed' => $submitted->count(),
            'in_progress' => $latestAttempts->filter(fn (?ExamAttempt $a) => $a?->status === ExamAttemptStatus::InProgress)->count(),
            'not_started' => $latestAttempts->filter(fn (?ExamAttempt $a) => $a === null)->count(),
            'average_score' => $scores->isNotEmpty() ? round($scores->avg(), 2) : null,
            'highest_score' => $scores->isNotEmpty() ? $scores->max() : null,
            'lowest_score' => $scores->isNotEmpty() ? $scores->min() : null,
        ];
    }

    /**
     * @param  Collection<int, KrsItem>  $participants
     */
    private function exportRecapCsv(Exam $exam, Collection $participants): StreamedResponse
    {
        $filename = 'rekap-nilai-'.Str::slug($exam->title).'.csv';

        return response()->streamDownload(function () use ($participants): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['No', 'NIM', 'Nama', 'Raw Score', 'Penalty', 'Final Score', 'Grade', 'Weighted Score', 'Status']);

            foreach ($participants as $index => $participant) {
                $attempt = $participant->examAttempts->first();
                fputcsv($handle, [
                    $index + 1,
                    $participant->student->nim,
                    $participant->student->name,
                    $attempt?->raw_score,
                    $attempt?->penalty_score,
                    $attempt?->score,
                    $attempt?->grade,
                    $attempt?->weighted_score,
                    $this->statusLabel($attempt),
                ]);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * @param  Collection<int, KrsItem>  $participants
     */
    private function exportRecapPdf(Exam $exam, Collection $participants): Response
    {
        $filename = 'rekap-nilai-'.Str::slug($exam->title).'.pdf';

        $rows = $participants->values()->map(fn (KrsItem $participant, int $index) => [
            'number' => $index + 1,
            'nim' => $participant->student->nim,
            'name' => $participant->student->name,
            'attempt' => $participant->examAttempts->first(),
            'status' => $this->statusLabel($participant->examAttempts->first()),
        ]);

        return Pdf::loadView('exams.recap-pdf', [
            'exam' => $exam,
            'summary' => $this->buildRecapSummary($exam, $participants),
            'rows' => $rows,
        ])->download($filename);
    }

    private function statusLabel(?ExamAttempt $attempt): string
    {
        return match (true) {
            $attempt === null => 'Belum Mengerjakan',
            $attempt->status === ExamAttemptStatus::InProgress => 'Sedang Mengerjakan',
            default => 'Selesai',
        };
    }
}
