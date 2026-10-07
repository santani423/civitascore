<?php

namespace Modules\Academic\Controllers;

use App\Http\Controllers\Controller;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Academic\Models\Assignment;
use Modules\Academic\Models\AssignmentSubmission;
use Modules\Academic\Models\ClassSection;
use Modules\Academic\Models\CourseMaterial;
use Modules\Academic\Requests\GradeAssignmentSubmissionRequest;
use Modules\Academic\Requests\UpsertAssignmentRequest;
use Modules\Academic\Requests\UpsertCourseMaterialRequest;
use Modules\Academic\Services\LearningService;
use Modules\Academic\Support\PortalFormatter;

/**
 * Sisi dosen pengampu (kelasnya sendiri) & Bagian Akademik (semua kelas):
 * materi, tugas, dan penilaian pengumpulan tugas. Otorisasi per kelas lewat
 * ClassSectionPolicy::manageLearning / AssignmentPolicy::manage.
 */
class TeachingController extends Controller
{
    public function __construct(private readonly LearningService $learning) {}

    public function classes(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'academic_term_id' => ['nullable', 'string', 'max:26'],
            'search' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $paginator = $this->learning->teachingClasses($request->user(), $filters);

        return response()->json([
            'success' => true,
            'message' => 'Berhasil.',
            'data' => collect($paginator->items())->map(fn (ClassSection $classSection): array => [
                ...PortalFormatter::classSection($classSection),
                'term' => PortalFormatter::term($classSection->academicTerm),
                'capacity' => $classSection->capacity,
                'enrolled_count' => (int) $classSection->getAttribute('enrolled_count'),
                'materials_count' => (int) $classSection->getAttribute('materials_count'),
                'assignments_count' => (int) $classSection->getAttribute('assignments_count'),
            ])->values(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    public function materials(ClassSection $classSection): JsonResponse
    {
        $this->authorize('manageLearning', [$classSection, 'course_materials.read']);

        return ApiResponse::success($this->learning->materialsForManager($classSection)
            ->map(fn (CourseMaterial $material) => $this->learning->formatMaterial($material))
            ->values());
    }

    public function storeMaterial(UpsertCourseMaterialRequest $request, ClassSection $classSection): JsonResponse
    {
        $this->authorize('manageLearning', [$classSection, 'course_materials.create']);

        $material = $this->learning->saveMaterial($classSection, $request->validated(), $request->user());

        return ApiResponse::success($this->learning->formatMaterial($material), 'Materi ditambahkan.', status: 201);
    }

    public function updateMaterial(UpsertCourseMaterialRequest $request, CourseMaterial $courseMaterial): JsonResponse
    {
        $this->authorize('manage', [$courseMaterial, 'course_materials.update']);

        $material = $this->learning->saveMaterial($courseMaterial->classSection, $request->validated(), $request->user(), $courseMaterial);

        return ApiResponse::success($this->learning->formatMaterial($material), 'Materi diperbarui.');
    }

    public function destroyMaterial(CourseMaterial $courseMaterial): JsonResponse
    {
        $this->authorize('manage', [$courseMaterial, 'course_materials.delete']);

        $courseMaterial->delete();

        return ApiResponse::success(null, 'Materi dihapus.');
    }

    public function assignments(ClassSection $classSection): JsonResponse
    {
        $this->authorize('manageLearning', [$classSection, 'assignments.read']);

        return ApiResponse::success($this->learning->assignmentsForManager($classSection)
            ->map(fn (Assignment $assignment) => $this->learning->formatAssignment($assignment))
            ->values());
    }

    public function storeAssignment(UpsertAssignmentRequest $request, ClassSection $classSection): JsonResponse
    {
        $this->authorize('manageLearning', [$classSection, 'assignments.create']);

        $assignment = $this->learning->saveAssignment($classSection, $request->validated(), $request->user());

        return ApiResponse::success($this->learning->formatAssignment($assignment), 'Tugas dibuat.', status: 201);
    }

    public function updateAssignment(UpsertAssignmentRequest $request, Assignment $assignment): JsonResponse
    {
        $this->authorize('manage', [$assignment, 'assignments.update']);

        $assignment = $this->learning->saveAssignment($assignment->classSection, $request->validated(), $request->user(), $assignment);

        return ApiResponse::success($this->learning->formatAssignment($assignment), 'Tugas diperbarui.');
    }

    public function destroyAssignment(Assignment $assignment): JsonResponse
    {
        $this->authorize('manage', [$assignment, 'assignments.delete']);

        $this->learning->deleteAssignment($assignment);

        return ApiResponse::success(null, 'Tugas dihapus.');
    }

    public function submissions(Assignment $assignment): JsonResponse
    {
        $this->authorize('manage', [$assignment, 'assignment_submissions.read']);

        return ApiResponse::success([
            'assignment' => $this->learning->formatAssignment($assignment->load('attachment')),
            'submissions' => $this->learning->submissionsForManager($assignment),
        ]);
    }

    public function grade(GradeAssignmentSubmissionRequest $request, AssignmentSubmission $assignmentSubmission): JsonResponse
    {
        $this->authorize('grade', $assignmentSubmission);

        $submission = $this->learning->grade($assignmentSubmission, $request->validated(), $request->user());

        return ApiResponse::success($this->learning->formatSubmission($submission), 'Nilai tugas disimpan.');
    }
}
