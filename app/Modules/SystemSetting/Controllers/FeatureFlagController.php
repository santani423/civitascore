<?php

namespace Modules\SystemSetting\Controllers;

use App\Http\Controllers\Controller;
use App\Support\Http\ApiResponse;
use App\Support\Http\ListQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\SystemSetting\Models\FeatureFlag;
use Modules\SystemSetting\Requests\UpdateFeatureFlagRequest;
use Modules\SystemSetting\Resources\FeatureFlagResource;
use Modules\SystemSetting\Services\FeatureFlagService;

class FeatureFlagController extends Controller
{
    public function __construct(private readonly FeatureFlagService $flags) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', FeatureFlag::class);

        $paginator = ListQuery::paginate(
            query: FeatureFlag::query(),
            request: $request,
            searchable: ['key', 'name'],
            filterable: ['is_enabled'],
            sortable: ['key', 'created_at'],
        );

        return ApiResponse::paginated(FeatureFlagResource::collection($paginator));
    }

    public function update(UpdateFeatureFlagRequest $request, FeatureFlag $featureFlag): JsonResponse
    {
        $this->authorize('update', FeatureFlag::class);

        $featureFlag = $this->flags->toggle($featureFlag, $request->validated('is_enabled'), $request->user());

        return ApiResponse::success(new FeatureFlagResource($featureFlag), 'Feature flag berhasil diperbarui.');
    }
}
