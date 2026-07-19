<?php

namespace Modules\Tenancy\Controllers;

use App\Http\Controllers\Controller;
use App\Support\Http\ApiResponse;
use App\Support\Http\Exceptions\ConflictException;
use App\Support\Http\ListQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Tenancy\Models\SupportSession;
use Modules\Tenancy\Requests\StartSupportSessionRequest;
use Modules\Tenancy\Resources\SupportSessionResource;

/**
 * Support Session — satu-satunya jalur sah Super Admin masuk ke konteks
 * satu universitas (lihat docs/RANCANGAN-SUPER-ADMIN.md §6.6). Dipasang di
 * grup /platform (bukan /tenant) karena store() adalah aksi platform yang
 * *membentuk* konteks tenant, belum berada di dalamnya.
 */
class SupportSessionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', SupportSession::class);

        $paginator = ListQuery::paginate(
            query: SupportSession::query()->with(['superAdmin', 'university'])->latest('started_at'),
            request: $request,
            searchable: [],
            filterable: ['university_id', 'super_admin_id'],
            sortable: ['started_at'],
        );

        return ApiResponse::paginated(SupportSessionResource::collection($paginator));
    }

    public function store(StartSupportSessionRequest $request): JsonResponse
    {
        $this->authorize('create', SupportSession::class);

        $session = SupportSession::create([
            'super_admin_id' => $request->user()->id,
            'university_id' => $request->validated('university_id'),
            'reason' => $request->validated('reason'),
            'started_at' => now(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return ApiResponse::success(
            new SupportSessionResource($session->load(['superAdmin', 'university'])),
            'Support session dimulai.',
            status: 201,
        );
    }

    public function end(SupportSession $supportSession): JsonResponse
    {
        $this->authorize('end', $supportSession);

        if ($supportSession->ended_at !== null) {
            throw new ConflictException('Support session ini sudah berakhir.');
        }

        $supportSession->update(['ended_at' => now()]);

        return ApiResponse::success(new SupportSessionResource($supportSession->load(['superAdmin', 'university'])), 'Support session diakhiri.');
    }
}
