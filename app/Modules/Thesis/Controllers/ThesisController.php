<?php

namespace Modules\Thesis\Controllers;

use App\Http\Controllers\Controller;
use App\Support\Http\ApiResponse;
use App\Support\Http\ListQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Thesis\Models\Thesis;
use Modules\Thesis\Resources\ThesisResource;

class ThesisController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Thesis::class);

        $paginator = ListQuery::paginate(
            query: Thesis::query()->with(['student', 'supervisor'])->orderByDesc('submitted_at'),
            request: $request,
            searchable: ['title'],
            filterable: ['status', 'thesis_type', 'student_id'],
            sortable: ['submitted_at'],
        );

        return ApiResponse::paginated(ThesisResource::collection($paginator));
    }

    public function show(Thesis $thesis): JsonResponse
    {
        $this->authorize('view', $thesis);

        return ApiResponse::success(new ThesisResource($thesis->load(['student', 'supervisor'])));
    }
}
