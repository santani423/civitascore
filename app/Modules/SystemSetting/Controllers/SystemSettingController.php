<?php

namespace Modules\SystemSetting\Controllers;

use App\Http\Controllers\Controller;
use App\Support\Http\ApiResponse;
use App\Support\Http\ListQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\SystemSetting\Models\SystemSetting;
use Modules\SystemSetting\Requests\UpdateSystemSettingRequest;
use Modules\SystemSetting\Resources\SystemSettingResource;
use Modules\SystemSetting\Services\SystemSettingService;

class SystemSettingController extends Controller
{
    public function __construct(private readonly SystemSettingService $settings) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', SystemSetting::class);

        $paginator = ListQuery::paginate(
            query: SystemSetting::query(),
            request: $request,
            searchable: ['key', 'group'],
            filterable: ['group', 'is_public'],
            sortable: ['key', 'created_at'],
        );

        return ApiResponse::paginated(SystemSettingResource::collection($paginator));
    }

    public function show(SystemSetting $systemSetting): JsonResponse
    {
        $this->authorize('view', $systemSetting);

        return ApiResponse::success(new SystemSettingResource($systemSetting));
    }

    public function update(UpdateSystemSettingRequest $request, SystemSetting $systemSetting): JsonResponse
    {
        $this->authorize('update', $systemSetting);

        $systemSetting = $this->settings->update($systemSetting, $request->validated('value'), $request->user());

        return ApiResponse::success(new SystemSettingResource($systemSetting), 'Setting berhasil diperbarui.');
    }
}
