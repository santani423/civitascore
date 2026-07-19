<?php

namespace Modules\Tenancy\Controllers;

use App\Http\Controllers\Controller;
use App\Support\Http\ApiResponse;
use App\Support\Http\ListQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Tenancy\Models\University;
use Modules\Tenancy\Requests\StoreUniversityRequest;
use Modules\Tenancy\Requests\UpdateUniversityRequest;
use Modules\Tenancy\Resources\UniversityResource;
use Modules\Tenancy\Services\UniversityService;

/**
 * Platform-level (Super Admin) university management — not behind
 * tenant.access, since operating across tenants is exactly the point.
 */
class UniversityController extends Controller
{
    public function __construct(private readonly UniversityService $universities) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', University::class);

        $paginator = ListQuery::paginate(
            query: University::query(),
            request: $request,
            searchable: ['name', 'code', 'slug'],
            filterable: ['status', 'is_active'],
            sortable: ['name', 'created_at'],
        );

        return ApiResponse::paginated(UniversityResource::collection($paginator));
    }

    public function store(StoreUniversityRequest $request): JsonResponse
    {
        $this->authorize('create', University::class);

        $university = $this->universities->create($request->validated(), $request->user());

        return ApiResponse::success(new UniversityResource($university), 'Universitas berhasil dibuat.', status: 201);
    }

    public function show(University $university): JsonResponse
    {
        $this->authorize('view', University::class);

        return ApiResponse::success(new UniversityResource($university->load(['domains', 'subscriptions.plan'])));
    }

    public function update(UpdateUniversityRequest $request, University $university): JsonResponse
    {
        $this->authorize('update', University::class);

        $university = $this->universities->update($university, $request->validated());

        return ApiResponse::success(new UniversityResource($university), 'Universitas berhasil diperbarui.');
    }

    public function activate(University $university): JsonResponse
    {
        $this->authorize('update', University::class);

        $university = $this->universities->activate($university);

        return ApiResponse::success(new UniversityResource($university), 'Universitas berhasil diaktifkan.');
    }

    public function suspend(University $university): JsonResponse
    {
        $this->authorize('update', University::class);

        $university = $this->universities->suspend($university);

        return ApiResponse::success(new UniversityResource($university), 'Universitas berhasil disuspend.');
    }
}
