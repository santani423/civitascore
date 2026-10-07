<?php

namespace Modules\Academic\Controllers;

use App\Http\Controllers\Controller;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Academic\Controllers\Concerns\ResolvesCurrentStudent;
use Modules\Academic\Requests\UpdateStudentProfileRequest;
use Modules\Academic\Services\StudentAcademicService;
use Modules\Academic\Services\StudentHistoryService;
use Modules\Academic\Services\StudentProfileService;

/**
 * Portal Mahasiswa — profil, ringkasan akademik ("Akademik Saya"), dan
 * riwayat akademik milik mahasiswa yang login.
 */
class StudentProfileController extends Controller
{
    use ResolvesCurrentStudent;

    public function __construct(
        private readonly StudentProfileService $profiles,
        private readonly StudentAcademicService $academics,
        private readonly StudentHistoryService $history,
    ) {}

    public function show(Request $request): JsonResponse
    {
        return ApiResponse::success($this->profiles->show($this->currentStudent($request)));
    }

    public function update(UpdateStudentProfileRequest $request): JsonResponse
    {
        $student = $this->profiles->update($this->currentStudent($request), $request->validated(), $request->user());

        return ApiResponse::success($this->profiles->show($student), 'Profil berhasil diperbarui.');
    }

    public function academicSummary(Request $request): JsonResponse
    {
        return ApiResponse::success($this->academics->summary($this->currentStudent($request)));
    }

    public function history(Request $request): JsonResponse
    {
        return ApiResponse::success($this->history->timeline($this->currentStudent($request)));
    }
}
