<?php

namespace Modules\Academic\Controllers;

use App\Http\Controllers\Controller;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Academic\Controllers\Concerns\ResolvesCurrentStudent;
use Modules\Academic\Models\Assignment;
use Modules\Academic\Models\ClassSection;
use Modules\Academic\Requests\SubmitAssignmentRequest;
use Modules\Academic\Services\LearningService;

/**
 * Portal Mahasiswa — mata kuliah yang diikuti, materi, dan tugas.
 */
class StudentLearningController extends Controller
{
    use ResolvesCurrentStudent;

    public function __construct(private readonly LearningService $learning) {}

    public function courses(Request $request): JsonResponse
    {
        return ApiResponse::success($this->learning->studentCourses($this->currentStudent($request)));
    }

    public function materials(Request $request): JsonResponse
    {
        return ApiResponse::success($this->learning->studentMaterials($this->currentStudent($request)));
    }

    public function course(Request $request, ClassSection $classSection): JsonResponse
    {
        $student = $this->currentStudent($request);
        $this->authorize('viewAsStudent', $classSection);

        return ApiResponse::success($this->learning->studentCourseDetail($student, $classSection));
    }

    public function assignments(Request $request): JsonResponse
    {
        $request->validate(['status' => ['nullable', 'string', 'in:all,not_submitted,submitted,late,graded,missed']]);

        return ApiResponse::success($this->learning->studentAssignments(
            $this->currentStudent($request),
            $request->query('status') ?: null,
        ));
    }

    public function assignment(Request $request, Assignment $assignment): JsonResponse
    {
        $student = $this->currentStudent($request);
        $this->authorize('viewAsStudent', $assignment);

        return ApiResponse::success($this->learning->studentAssignmentDetail($student, $assignment));
    }

    public function submit(SubmitAssignmentRequest $request, Assignment $assignment): JsonResponse
    {
        $student = $this->currentStudent($request);
        $this->authorize('submit', $assignment);

        $this->learning->submit($student, $assignment, $request->validated(), $request->user());

        return ApiResponse::success(
            $this->learning->studentAssignmentDetail($student, $assignment->refresh()),
            'Tugas berhasil dikumpulkan.',
        );
    }
}
