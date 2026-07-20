<?php

namespace Modules\Alumni\Controllers;

use App\Http\Controllers\Controller;
use App\Support\Http\ApiResponse;
use App\Support\Http\ListQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Alumni\Models\Alumni;
use Modules\Alumni\Resources\AlumniResource;

class AlumniController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Alumni::class);

        $paginator = ListQuery::paginate(
            query: Alumni::query()->with('student.studyProgram')->orderByDesc('graduation_year'),
            request: $request,
            filterable: ['employment_status', 'graduation_year'],
            sortable: ['graduation_year'],
        );

        return ApiResponse::paginated(AlumniResource::collection($paginator));
    }

    public function show(Alumni $alumni): JsonResponse
    {
        $this->authorize('view', $alumni);

        return ApiResponse::success(new AlumniResource($alumni->load('student.studyProgram')));
    }
}
