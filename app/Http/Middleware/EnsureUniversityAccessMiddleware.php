<?php

namespace App\Http\Middleware;

use App\Support\Http\ApiResponse;
use App\Support\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Modules\Tenancy\Enums\MembershipStatus;
use Modules\Tenancy\Models\UserUniversity;
use Symfony\Component\HttpFoundation\Response;

/**
 * Applied to tenant-business route groups (not platform/super-admin
 * routes). Requires a resolved university (see ResolveUniversityMiddleware)
 * AND that the authenticated user holds an active membership in it — a
 * super_admin bypasses the membership check (platform-wide access) but
 * still requires a resolved university, since "acting on a tenant" without
 * knowing which tenant is never valid here.
 */
class EnsureUniversityAccessMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return ApiResponse::error('Tidak terautentikasi.', status: 401);
        }

        $universityId = app(TenantContext::class)->universityId();

        if ($universityId === null) {
            return ApiResponse::error('Universitas tidak dapat ditentukan untuk permintaan ini.', status: 400);
        }

        if ($user->hasRole('super_admin')) {
            return $next($request);
        }

        $isMember = UserUniversity::query()
            ->where('user_id', $user->id)
            ->where('university_id', $universityId)
            ->where('status', MembershipStatus::Active)
            ->exists();

        if (! $isMember) {
            return ApiResponse::error('Anda tidak terdaftar pada universitas ini.', status: 403);
        }

        return $next($request);
    }
}
