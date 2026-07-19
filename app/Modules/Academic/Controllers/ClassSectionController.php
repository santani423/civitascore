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
            query: ClassSection::query()->with(['studyProgram', 'academicTerm'])->orderBy('course_name'),
            request: $request,
            searchable: ['course_name', 'class_code'],
            filterable: ['study_program_id', 'academic_term_id', 'is_active'],
            sortable: ['course_name'],
        );

        return ApiResponse::paginated(ClassSectionResource::collection($paginator));
    }

    public function show(ClassSection $classSection): JsonResponse
    {
        $this->authorize('view', $classSection);

        return ApiResponse::success(new ClassSectionResource($classSection->load(['studyProgram', 'academicTerm'])));
    }
}
