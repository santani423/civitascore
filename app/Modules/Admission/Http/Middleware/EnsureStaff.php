<?php

namespace Modules\Admission\Http\Middleware;

use App\Models\User;
use App\Support\Http\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Token pendaftar tidak boleh memanggil /pmb/admin/* (13 §B.1). Dipasang
 * SEBELUM `permission:` agar EnsurePermission selalu menerima `User`.
 */
class EnsureStaff
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user() instanceof User) {
            return ApiResponse::error('Anda tidak memiliki izin untuk mengakses resource ini.', status: 403);
        }

        $response = $next($request);
        $response->headers->set('Cache-Control', 'no-store, private');

        return $response;
    }
}
