<?php

namespace Modules\Academic\Controllers;

use App\Http\Controllers\Controller;
use App\Support\Http\ApiResponse;
use App\Support\Http\ListQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Academic\Models\ClassSection;
use Modules\Academic\Resources\ClassSectionResource;
use Modules\Academic\Support\ClassSectionAccess;

class ClassSectionController extends Controller
{
    public function __construct(private readonly ClassSectionAccess $access) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', ClassSection::class);

        $query = ClassSection::query()
            ->with(['studyProgram', 'academicTerm', 'course', 'lecturer', 'schedules'])
            ->withCount('krsItems')
            ->orderBy('class_code');
        $this->access->restrictToOwnClasses($query, $request->user(), column: 'id');

        // Kelas tanpa dosen pengampu harus diisi Bagian Akademik sebelum
        // kepemilikan dosen ditegakkan. Bukan kolom, jadi diterapkan manual
        // (pendekatan sama dengan filter has_account di LecturerController).
        $hasLecturer = $request->input('filter.has_lecturer');

        if ($hasLecturer !== null && $hasLecturer !== '') {
            filter_var($hasLecturer, FILTER_VALIDATE_BOOLEAN)
                ? $query->whereNotNull('lecturer_id')
                : $query->whereNull('lecturer_id');
        }

        $paginator = ListQuery::paginate(
            query: $query,
            request: $request,
            searchable: ['class_code'],
            filterable: ['study_program_id', 'academic_term_id', 'course_id', 'lecturer_id', 'is_active'],
            sortable: ['class_code'],
        );

        return ApiResponse::paginated(ClassSectionResource::collection($paginator));
    }

    public function show(ClassSection $classSection): JsonResponse
    {
        $this->authorize('view', $classSection);

        return ApiResponse::success(new ClassSectionResource(
            $classSection->load(['studyProgram', 'academicTerm', 'course', 'lecturer', 'schedules'])->loadCount('krsItems'),
        ));
    }
}
