<?php

namespace Modules\Academic\Controllers;

use App\Http\Controllers\Controller;
use App\Support\Http\ApiResponse;
use App\Support\Http\ListQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Academic\Models\Exam;
use Modules\Academic\Requests\StoreExamRequest;
use Modules\Academic\Requests\UpdateExamRequest;
use Modules\Academic\Resources\ExamResource;
use Modules\Academic\Services\ExamService;

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
}
