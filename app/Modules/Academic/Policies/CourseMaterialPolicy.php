<?php

namespace Modules\Academic\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;
use Modules\Academic\Models\CourseMaterial;
use Modules\Academic\Support\ClassSectionAccess;

class CourseMaterialPolicy
{
    public function __construct(private readonly ClassSectionAccess $access) {}

    public function manage(User $user, CourseMaterial $material, string $permission): Response
    {
        return $this->access->canManage($user, $material->classSection, $permission)
            ? Response::allow()
            : Response::deny('Anda bukan dosen pengampu kelas ini.');
    }
}
