<?php

namespace Modules\Academic\Controllers;

use App\Http\Controllers\Controller;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Academic\Models\Lecturer;
use Modules\Academic\Requests\UpdateLecturerProfileRequest;
use Modules\Academic\Resources\LecturerResource;
use Modules\Academic\Support\LecturerIdentity;

/**
 * Self-service "Profil Saya" for the logged-in dosen. The lecturer is
 * always resolved from auth()->user() via LecturerIdentity (lecturers.user_id,
 * falling back to the SDM employee link; TenantScoped to the current
 * university) — never from a client-supplied id — so a dosen can only ever
 * see/edit their own record.
 */
class LecturerProfileController extends Controller
{
    public function __construct(private readonly LecturerIdentity $identity) {}

    public function show(Request $request): JsonResponse
    {
        $lecturer = $this->currentLecturer($request);

        if ($lecturer === null) {
            return $this->notLinked();
        }

        return ApiResponse::success(new LecturerResource($lecturer));
    }

    public function update(UpdateLecturerProfileRequest $request): JsonResponse
    {
        $lecturer = $this->currentLecturer($request);

        if ($lecturer === null) {
            return $this->notLinked();
        }

        $lecturer->update($request->validated());

        return ApiResponse::success(new LecturerResource($lecturer), 'Profil berhasil diperbarui.');
    }

    private function currentLecturer(Request $request): ?Lecturer
    {
        return $this->identity->lecturerFor($request->user())?->load(['faculty', 'user']);
    }

    private function notLinked(): JsonResponse
    {
        return ApiResponse::error('Akun Anda belum tertaut ke data dosen. Hubungi Bagian SDM.', status: 404);
    }
}
