<?php

namespace Modules\Academic\Controllers;

use App\Http\Controllers\Controller;
use App\Support\Http\ApiResponse;
use App\Support\Http\ListQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Academic\Models\Lecturer;
use Modules\Academic\Resources\LecturerResource;

class LecturerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Lecturer::class);

        $paginator = ListQuery::paginate(
            query: Lecturer::query()->with('faculty')->orderBy('name'),
            request: $request,
            searchable: ['name', 'nidn'],
            filterable: ['faculty_id', 'is_active'],
            sortable: ['name'],
        );

        return ApiResponse::paginated(LecturerResource::collection($paginator));
    }

    public function show(Lecturer $lecturer): JsonResponse
    {
        $this->authorize('view', $lecturer);

        return ApiResponse::success(new LecturerResource($lecturer->load('faculty')));
    }
}
