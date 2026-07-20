<?php

namespace Modules\Academic\Controllers;

use App\Http\Controllers\Controller;
use App\Support\Http\ApiResponse;
use App\Support\Http\ListQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Academic\Models\Curriculum;
use Modules\Academic\Resources\CurriculumResource;

class CurriculumController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Curriculum::class);

        $paginator = ListQuery::paginate(
            query: Curriculum::query()->with('studyProgram')->withCount('courses')->orderBy('name'),
            request: $request,
            searchable: ['name', 'academic_year'],
            filterable: ['study_program_id', 'is_active'],
            sortable: ['name', 'academic_year'],
        );

        return ApiResponse::paginated(CurriculumResource::collection($paginator));
    }

    public function show(Curriculum $curriculum): JsonResponse
    {
        $this->authorize('view', $curriculum);

        return ApiResponse::success(new CurriculumResource(
            $curriculum->load(['studyProgram', 'courses'])->loadCount('courses'),
        ));
    }
}
