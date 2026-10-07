<?php

namespace Modules\Academic\Controllers;

use App\Http\Controllers\Controller;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Modules\Academic\Models\Lecturer;
use Modules\Academic\Requests\ResetLecturerPasswordRequest;
use Modules\Academic\Requests\UpdateLecturerAccountStatusRequest;
use Modules\Academic\Resources\LecturerResource;
use Modules\Academic\Services\LecturerAccountService;

/**
 * SDM-side management of a dosen's login account. All business rules
 * (who may be touched, sync with tenant membership/role) live in
 * LecturerAccountService.
 */
class LecturerAccountController extends Controller
{
    public function __construct(private readonly LecturerAccountService $accounts) {}

    /**
     * Provisions an account for a lecturer registered without one (e.g.
     * data imported before accounts existed).
     */
    public function store(ResetLecturerPasswordRequest $request, Lecturer $lecturer): JsonResponse
    {
        $this->authorize('manageAccount', $lecturer);

        $result = $this->accounts->provision($lecturer, $request->validated('password'), $request->user());

        return ApiResponse::success(
            $this->resource($lecturer),
            $result['created'] ? 'Akun login dosen berhasil dibuat.' : 'Dosen berhasil ditautkan ke akun yang sudah ada.',
            meta: ['credentials' => [
                'email' => $result['user']->email,
                'password' => $result['password'],
                'account_created' => $result['created'],
            ]],
            status: 201,
        );
    }

    public function resetPassword(ResetLecturerPasswordRequest $request, Lecturer $lecturer): JsonResponse
    {
        $this->authorize('manageAccount', $lecturer);

        $password = $this->accounts->resetPassword($lecturer, $request->validated('password'));

        return ApiResponse::success(
            $this->resource($lecturer),
            'Password akun dosen berhasil direset. Dosen wajib mengganti password saat login berikutnya.',
            meta: ['credentials' => [
                'email' => (string) $lecturer->user?->email,
                'password' => $password,
                'account_created' => false,
            ]],
        );
    }

    public function updateStatus(UpdateLecturerAccountStatusRequest $request, Lecturer $lecturer): JsonResponse
    {
        $this->authorize('manageAccount', $lecturer);

        $active = $request->boolean('is_active');
        $this->accounts->setAccountActive($lecturer, $active);

        return ApiResponse::success(
            $this->resource($lecturer),
            $active ? 'Akun login dosen diaktifkan.' : 'Akun login dosen dinonaktifkan.',
        );
    }

    private function resource(Lecturer $lecturer): LecturerResource
    {
        $lecturer->load(['faculty', 'user']);

        return (new LecturerResource($lecturer))->withAccountManageable(
            $lecturer->user ? $this->accounts->isManageable($lecturer->user, $lecturer->university_id) : null,
        );
    }
}
