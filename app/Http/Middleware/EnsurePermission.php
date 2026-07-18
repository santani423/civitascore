<?php

namespace App\Http\Middleware;

use App\Support\Http\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Route-level, coarse permission check: `permission:users.read` or, for an
 * OR match, `permission:users.read,users.export`. Fine-grained/instance-aware
 * checks still belong in a Policy — both delegate to the same PermissionRegistry.
 */
class EnsurePermission
{
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = $request->user();

        if (! $user) {
            return ApiResponse::error('Tidak terautentikasi.', status: 401);
        }

        foreach ($permissions as $permission) {
            if ($user->hasPermissionTo($permission)) {
                return $next($request);
            }
        }

        return ApiResponse::error('Anda tidak memiliki izin untuk mengakses resource ini.', status: 403);
    }
}
