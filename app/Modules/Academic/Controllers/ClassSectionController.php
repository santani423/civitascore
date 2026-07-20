<?php

namespace Modules\Academic\Controllers;

use App\Http\Controllers\Controller;
use App\Support\Http\ApiResponse;
use App\Support\Http\ListQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Academic\Models\ClassSection;
use Modules\Academic\Resources\ClassSectionResource;

class ClassSectionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', ClassSection::class);

        $paginator = ListQuery::paginate(
            query: ClassSection::query()->with(['studyProgram', 'academicTerm', 'course'])->withCount('krsItems')->orderBy('class_code'),
            request: $request,
            searchable: ['class_code'],
            filterable: ['study_program_id', 'academic_term_id', 'course_id', 'is_active'],
            sortable: ['class_code'],
        );

        return ApiResponse::paginated(ClassSectionResource::collection($paginator));
    }

    public function show(ClassSection $classSection): JsonResponse
    {
        $this->authorize('view', $classSection);

        return ApiResponse::success(new ClassSectionResource(
            $classSection->load(['studyProgram', 'academicTerm', 'course'])->loadCount('krsItems'),
        ));
    }
}
