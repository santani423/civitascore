<?php

namespace Modules\Academic\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;
use Modules\Academic\Models\StudentRequest;

/**
 * Mahasiswa hanya melihat & mengubah pengajuannya sendiri (pengajuan orang
 * lain dijawab 404). Peninjau (Bagian Akademik) memakai approval_requests.read
 * untuk melihat; hak memutuskan tetap dicek per langkah persetujuan oleh
 * ApprovalRequestStepPolicy::act (lihat StudentRequestService::actionableStep).
 */
class StudentRequestPolicy
{
    public function viewOwn(User $user, StudentRequest $request): Response
    {
        return $user->student !== null && $user->student->id === $request->student_id
            ? Response::allow()
            : Response::denyAsNotFound('Data tidak ditemukan.');
    }

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('approval_requests.read');
    }

    public function view(User $user, StudentRequest $request): bool
    {
        return $user->hasPermissionTo('approval_requests.read');
    }
}
