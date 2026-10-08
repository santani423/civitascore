<?php

namespace Modules\Academic\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;
use Modules\Academic\Models\ClassSection;
use Modules\Academic\Support\ClassSectionAccess;

class GradePolicy
{
    public function __construct(private readonly ClassSectionAccess $access) {}

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('grades.read');
    }

    /**
     * Satu ability untuk upsert nilai — create dan update memakai endpoint
     * yang sama. Bila kepemilikan dosen ditegakkan, hanya dosen pengampu
     * kelas itu (atau Bagian Akademik) yang boleh.
     */
    public function manage(User $user, ClassSection $classSection): Response
    {
        if (! $user->hasPermissionTo('grades.create') && ! $user->hasPermissionTo('grades.update')) {
            return Response::deny();
        }

        return $this->access->canWriteTeaching($user, $classSection)
            ? Response::allow()
            : Response::deny('Anda bukan dosen pengampu kelas ini.');
    }
}
