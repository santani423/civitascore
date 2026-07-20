<?php

namespace Modules\Scholarship\Controllers;

use App\Http\Controllers\Controller;
use App\Support\Http\ApiResponse;
use App\Support\Http\ListQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Scholarship\Models\Scholarship;
use Modules\Scholarship\Resources\ScholarshipResource;

class ScholarshipController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Scholarship::class);

        $paginator = ListQuery::paginate(
            query: Scholarship::query()->withCount('applications')->orderBy('name'),
            request: $request,
            searchable: ['name', 'provider'],
            filterable: ['is_active'],
            sortable: ['name', 'academic_year'],
        );

        return ApiResponse::paginated(ScholarshipResource::collection($paginator));
    }

    public function show(Scholarship $scholarship): JsonResponse
    {
        $this->authorize('view', $scholarship);

        return ApiResponse::success(new ScholarshipResource(
            $scholarship->load(['applications.student'])->loadCount('applications'),
        ));
    }
}
