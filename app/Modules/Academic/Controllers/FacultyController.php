<?php

namespace Modules\Academic\Controllers;

use App\Http\Controllers\Controller;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Modules\Academic\Models\Faculty;

/**
 * Lightweight faculty options list (e.g. the faculty picker on the Dosen
 * form). Faculties are few per tenant, so this is unpaginated.
 */
class FacultyController extends Controller
{
    public function index(): JsonResponse
    {
        $faculties = Faculty::query()
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'is_active'])
            ->map(fn (Faculty $faculty): array => [
                'id' => $faculty->id,
                'code' => $faculty->code,
                'name' => $faculty->name,
                'is_active' => (bool) $faculty->is_active,
            ])
            ->all();

        return ApiResponse::success($faculties);
    }
}
