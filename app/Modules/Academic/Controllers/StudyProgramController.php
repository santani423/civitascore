<?php

namespace Modules\Academic\Controllers;

use App\Http\Controllers\Controller;
use App\Support\Http\ApiResponse;
use App\Support\Http\ListQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Academic\Models\StudyProgram;
use Modules\Academic\Resources\StudyProgramResource;

class StudyProgramController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', StudyProgram::class);

        $paginator = ListQuery::paginate(
            query: StudyProgram::query()->with('faculty')->withCount(['students', 'classSections'])->orderBy('name'),
            request: $request,
            searchable: ['name', 'code'],
            filterable: ['faculty_id', 'is_active'],
            sortable: ['name'],
        );

        return ApiResponse::paginated(StudyProgramResource::collection($paginator));
    }

    public function show(StudyProgram $studyProgram): JsonResponse
    {
        $this->authorize('view', $studyProgram);

        return ApiResponse::success(new StudyProgramResource(
            $studyProgram->load('faculty')->loadCount(['students', 'classSections']),
        ));
    }
}
