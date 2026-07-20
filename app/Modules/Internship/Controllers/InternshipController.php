<?php

namespace Modules\Internship\Controllers;

use App\Http\Controllers\Controller;
use App\Support\Http\ApiResponse;
use App\Support\Http\ListQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Internship\Models\Internship;
use Modules\Internship\Resources\InternshipResource;

class InternshipController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Internship::class);

        $paginator = ListQuery::paginate(
            query: Internship::query()->with(['student', 'supervisor'])->orderByDesc('start_date'),
            request: $request,
            searchable: ['institution_name'],
            filterable: ['status', 'program_type', 'student_id'],
            sortable: ['start_date'],
        );

        return ApiResponse::paginated(InternshipResource::collection($paginator));
    }

    public function show(Internship $internship): JsonResponse
    {
        $this->authorize('view', $internship);

        return ApiResponse::success(new InternshipResource($internship->load(['student', 'supervisor'])));
    }
}
