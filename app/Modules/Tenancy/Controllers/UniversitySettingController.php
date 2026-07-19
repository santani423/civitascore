<?php

namespace Modules\Tenancy\Controllers;

use App\Http\Controllers\Controller;
use App\Support\Http\ApiResponse;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Tenancy\Models\UniversitySetting;
use Modules\Tenancy\Requests\UpsertUniversitySettingRequest;
use Modules\Tenancy\Resources\UniversitySettingResource;

/**
 * Tenant self-service configuration (branding.*, security.*, notification.*,
 * storage.*, ... via the `group` column) — see university_settings
 * migration docblock. Always implicitly scoped to the resolved tenant
 * (tenant.access middleware guarantees TenantContext is set on this route
 * group), never to an arbitrary university_id from the client.
 */
class UniversitySettingController extends Controller
{
    public function __construct(private readonly TenantContext $tenant) {}

    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()->hasPermissionTo('tenant_profile.read'), 403, 'Anda tidak memiliki izin untuk mengakses resource ini.');

        $settings = UniversitySetting::query()
            ->where('university_id', $this->tenant->universityId())
            ->orderBy('group')
            ->orderBy('key')
            ->get();

        return ApiResponse::success(UniversitySettingResource::collection($settings));
    }

    public function upsert(UpsertUniversitySettingRequest $request): JsonResponse
    {
        abort_unless($request->user()->hasPermissionTo('tenant_profile.update'), 403, 'Anda tidak memiliki izin untuk mengakses resource ini.');

        $setting = UniversitySetting::query()->updateOrCreate(
            ['university_id' => $this->tenant->universityId(), 'key' => $request->validated('key')],
            [
                'value' => $request->validated('value'),
                'type' => $request->validated('type'),
                'group' => $request->validated('group'),
                'description' => $request->validated('description'),
                'is_public' => $request->boolean('is_public'),
                'updated_by' => $request->user()->id,
            ],
        );

        return ApiResponse::success(new UniversitySettingResource($setting), 'Pengaturan universitas berhasil disimpan.');
    }
}
