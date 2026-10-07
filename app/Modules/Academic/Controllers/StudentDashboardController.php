<?php

namespace Modules\Academic\Controllers;

use App\Http\Controllers\Controller;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Academic\Controllers\Concerns\ResolvesCurrentStudent;
use Modules\Academic\Services\StudentDashboardService;
use Modules\Academic\Services\StudentDocumentService;

/**
 * Portal Mahasiswa — dashboard, daftar mata kuliah, dan daftar dokumen.
 */
class StudentDashboardController extends Controller
{
    use ResolvesCurrentStudent;

    public function __construct(
        private readonly StudentDashboardService $dashboard,
        private readonly StudentDocumentService $documents,
    ) {}

    public function show(Request $request): JsonResponse
    {
        return ApiResponse::success($this->dashboard->build($this->currentStudent($request), $request->user()));
    }

    public function documents(Request $request): JsonResponse
    {
        return ApiResponse::success($this->documents->available($this->currentStudent($request)));
    }
}
