<?php

namespace Modules\Academic\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;
use Modules\Academic\Models\AssignmentSubmission;
use Modules\Academic\Support\ClassSectionAccess;

class AssignmentSubmissionPolicy
{
    public function __construct(private readonly ClassSectionAccess $access) {}

    /** Menilai pengumpulan: dosen pengampu kelas tugas itu, atau Bagian Akademik. */
    public function grade(User $user, AssignmentSubmission $submission): Response
    {
        return $this->access->canManage($user, $submission->assignment->classSection, 'assignment_submissions.update')
            ? Response::allow()
            : Response::deny('Anda bukan dosen pengampu kelas ini.');
    }
}
