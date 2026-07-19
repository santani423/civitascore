<?php

namespace Modules\Tenancy\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Tenancy\Enums\UniversityStatus;
use Modules\Tenancy\Models\University;
use Modules\Tenancy\Models\UserUniversity;

class PlatformStatisticsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()->hasPermissionTo('platform_statistics.read'), 403, 'Anda tidak memiliki izin untuk mengakses resource ini.');

        $byStatus = University::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return ApiResponse::success([
            'universities_total' => University::query()->count(),
            'universities_active' => University::query()->where('status', UniversityStatus::Active)->count(),
            'universities_by_status' => $byStatus,
            'users_total' => User::query()->count(),
            'memberships_total' => UserUniversity::query()->count(),
        ]);
    }
}
