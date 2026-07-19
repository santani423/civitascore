<?php

namespace App\Http\Middleware;

use App\Support\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Modules\Tenancy\Enums\MembershipStatus;
use Modules\Tenancy\Models\UniversityDomain;
use Modules\Tenancy\Models\UserUniversity;
use Symfony\Component\HttpFoundation\Response;

/**
 * Determines "which university is this request for" and records it on
 * TenantContext — applied globally to every /api/v1 route (see
 * routes/api.php) so tenant-scoped models are never queried without a
 * resolved (possibly null/platform) context. Does NOT authorize access —
 * that's EnsureUniversityAccessMiddleware, applied only to tenant-business
 * route groups. Safe to run for guests/unauthenticated requests.
 *
 * Resolution order: explicit X-University-ID header (client-claimed, not
 * yet trusted — EnsureUniversityAccessMiddleware verifies membership) ->
 * custom/sub domain lookup -> the authenticated user's default active
 * membership.
 */
class ResolveUniversityMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $universityId = $request->header('X-University-ID');

        if (! $universityId) {
            $universityId = UniversityDomain::query()->where('domain', $request->getHost())->value('university_id');
        }

        if (! $universityId && $request->user()) {
            $universityId = UserUniversity::query()
                ->where('user_id', $request->user()->id)
                ->where('status', MembershipStatus::Active)
                ->orderByDesc('is_default')
                ->value('university_id');
        }

        app(TenantContext::class)->setUniversityId($universityId);

        return $next($request);
    }
}
