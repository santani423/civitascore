<?php

namespace Modules\Academic\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;
use Modules\Academic\Models\Assignment;
use Modules\Academic\Support\ClassSectionAccess;

class AssignmentPolicy
{
    public function __construct(private readonly ClassSectionAccess $access) {}

    /** Portal Mahasiswa: tugas yang dipublikasikan di kelas yang diikutinya. */
    public function viewAsStudent(User $user, Assignment $assignment): Response
    {
        return $assignment->is_published && $this->access->isEnrolled($user, $assignment->class_section_id)
            ? Response::allow()
            : Response::denyAsNotFound('Data tidak ditemukan.');
    }

    public function submit(User $user, Assignment $assignment): Response
    {
        if (! $user->hasPermissionTo('assignment_submissions.create')) {
            return Response::deny();
        }

        return $this->viewAsStudent($user, $assignment);
    }

    public function manage(User $user, Assignment $assignment, string $permission): Response
    {
        return $this->access->canManage($user, $assignment->classSection, $permission)
            ? Response::allow()
            : Response::deny('Anda bukan dosen pengampu kelas ini.');
    }
}
