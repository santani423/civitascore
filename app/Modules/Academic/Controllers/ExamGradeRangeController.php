<?php

namespace Modules\Academic\Controllers;

use App\Http\Controllers\Controller;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Modules\Academic\Models\Exam;
use Modules\Academic\Requests\UpsertExamGradeRangesRequest;
use Modules\Academic\Resources\ExamGradeRangeResource;
use Modules\Academic\Services\ExamService;

/** Konfigurasi rentang nilai huruf per ujian (spec §8) — lihat ExamService::resolveGrade()/upsertGradeRanges(). */
class ExamGradeRangeController extends Controller
{
    public function __construct(private readonly ExamService $exams) {}

    public function index(Exam $exam): JsonResponse
    {
        $this->authorize('viewAny', Exam::class);

        return ApiResponse::success(ExamGradeRangeResource::collection($exam->gradeRanges()->orderBy('min_score')->get()));
    }

    public function update(UpsertExamGradeRangesRequest $request, Exam $exam): JsonResponse
    {
        $this->authorize('manage', Exam::class);

        $ranges = $this->exams->upsertGradeRanges($exam, $request->validated('ranges'));

        return ApiResponse::success(ExamGradeRangeResource::collection($ranges), 'Rentang nilai berhasil disimpan.');
    }
}
