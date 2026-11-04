<?php

namespace Modules\SystemSetting\Controllers;

use App\Http\Controllers\Controller;
use App\Support\Http\ApiResponse;
use App\Support\Http\Exceptions\ConflictException;
use App\Support\Http\ListQuery;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Academic\Support\AcademicFeature;
use Modules\Academic\Support\LecturerOwnershipReadiness;
use Modules\SystemSetting\Models\FeatureFlag;
use Modules\SystemSetting\Requests\UpdateFeatureFlagRequest;
use Modules\SystemSetting\Resources\FeatureFlagResource;
use Modules\SystemSetting\Services\FeatureFlagService;

/**
 * Daftar flag beserta nilai efektifnya. Di konteks tenant, perubahan
 * hanya berlaku untuk universitas itu (override) — lihat
 * FeatureFlagService::set(); konteks platform mengubah nilai global.
 */
class FeatureFlagController extends Controller
{
    public function __construct(
        private readonly FeatureFlagService $flags,
        private readonly TenantContext $tenant,
        private readonly LecturerOwnershipReadiness $lecturerOwnership,
    ) {}

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

        $enabled = $request->validated('is_enabled');

        if ($enabled && $featureFlag->key === AcademicFeature::LECTURER_OWNERSHIP) {
            $unassigned = $this->lecturerOwnership->unassignedClassCount();

            if ($unassigned > 0) {
                throw new ConflictException(sprintf(
                    'Isi dosen pengampu untuk %d kelas aktif terlebih dahulu sebelum mengaktifkan kepemilikan dosen.',
                    $unassigned,
                ));
            }
        }

        $featureFlag = $this->flags->set($featureFlag, $enabled, $request->user());

        return ApiResponse::success(
            new FeatureFlagResource($featureFlag),
            $this->tenant->hasUniversity()
                ? 'Feature flag untuk universitas ini berhasil diperbarui.'
                : 'Feature flag berhasil diperbarui.',
        );
    }

    /** Universitas aktif kembali mengikuti nilai global flag ini. */
    public function clearOverride(FeatureFlag $featureFlag): JsonResponse
    {
        $this->authorize('update', FeatureFlag::class);

        $universityId = $this->tenant->universityId()
            ?? throw new ConflictException('Pilih universitas terlebih dahulu — nilai global tidak memiliki override.');

        $this->flags->clearOverride($featureFlag, $universityId);

        return ApiResponse::success(new FeatureFlagResource($featureFlag), 'Feature flag kembali mengikuti pengaturan global.');
    }
}
