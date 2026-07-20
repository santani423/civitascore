<?php

namespace Modules\Academic\Controllers;

use App\Http\Controllers\Controller;
use App\Support\Http\ApiResponse;
use App\Support\Http\ListQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Academic\Models\Course;
use Modules\Academic\Resources\CourseResource;

class CourseController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Course::class);

        $paginator = ListQuery::paginate(
            query: Course::query()->with(['studyProgram', 'curriculum'])->orderBy('code'),
            request: $request,
            searchable: ['code', 'name'],
            filterable: ['study_program_id', 'curriculum_id', 'semester_level', 'is_active'],
            sortable: ['code', 'name', 'credits', 'semester_level'],
        );

        return ApiResponse::paginated(CourseResource::collection($paginator));
    }

    public function show(Course $course): JsonResponse
    {
        $this->authorize('view', $course);

        return ApiResponse::success(new CourseResource($course->load(['studyProgram', 'curriculum'])));
    }
}
