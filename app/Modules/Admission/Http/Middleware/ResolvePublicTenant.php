<?php

namespace Modules\Admission\Http\Middleware;

use App\Support\Http\ApiResponse;
use App\Support\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Modules\Tenancy\Models\University;
use Symfony\Component\HttpFoundation\Response;

/**
 * Endpoint publik & autentikasi pendaftar: tenant dari domain website PMB
 * (A-19, sudah dicoba ResolveUniversityMiddleware) atau header
 * `X-University-ID` / `X-University-Code` (pengembangan & SPA lintas domain).
 * Universitas harus berstatus aktif dan modul `admission` aktif.
 */
class ResolvePublicTenant
{
    public function __construct(private readonly TenantContext $tenant) {}

    public function handle(Request $request, Closure $next): Response
    {
        $universityId = $this->tenant->universityId();

        if ($universityId === null && ($code = $request->header('X-University-Code'))) {
            $universityId = University::query()->where('code', $code)->orWhere('slug', $code)->value('id');
        }

        $university = $universityId ? University::query()->find($universityId) : null;

        if ($university === null || ! $university->is_active) {
            return ApiResponse::error('Universitas tidak dapat ditentukan untuk permintaan ini.', status: 400);
        }

        $this->tenant->setUniversityId((string) $university->getKey());

        return $next($request);
    }
}
