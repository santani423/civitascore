<?php

namespace Modules\Tenancy\Controllers;

use App\Http\Controllers\Controller;
use App\Support\Http\ApiResponse;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Modules\Tenancy\Models\University;
use Modules\Tenancy\Resources\UniversityResource;

/**
 * Tenant self-service — "who am I logged into" for the currently resolved
 * university. Gated by the tenant.access route middleware (membership
 * required), not by a fine-grained permission — any active member of a
 * tenant can see its own profile.
 */
class TenantProfileController extends Controller
{
    public function __construct(private readonly TenantContext $tenant) {}

    public function show(): JsonResponse
    {
        $university = University::query()->findOrFail($this->tenant->universityId());

        return ApiResponse::success(new UniversityResource($university->load(['domains', 'subscriptions.plan'])));
    }
}
