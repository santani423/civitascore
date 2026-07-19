<?php

namespace Modules\Academic\Controllers;

use App\Http\Controllers\Controller;
use App\Support\Http\ApiResponse;
use App\Support\Http\ListQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Academic\Models\Employee;
use Modules\Academic\Resources\EmployeeResource;

class EmployeeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Employee::class);

        $paginator = ListQuery::paginate(
            query: Employee::query()->orderBy('name'),
            request: $request,
            searchable: ['name', 'position'],
            filterable: ['unit_kerja', 'is_active'],
            sortable: ['name'],
        );

        return ApiResponse::paginated(EmployeeResource::collection($paginator));
    }

    public function show(Employee $employee): JsonResponse
    {
        $this->authorize('view', $employee);

        return ApiResponse::success(new EmployeeResource($employee));
    }
}
