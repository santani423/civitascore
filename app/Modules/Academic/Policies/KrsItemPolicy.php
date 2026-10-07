<?php

namespace Modules\Academic\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;
use Modules\Academic\Models\KrsItem;

class KrsItemPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('krs.read');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('krs.create');
    }

    public function drop(User $user, KrsItem $krsItem): bool
    {
        return $user->hasPermissionTo('krs.update');
    }

    /**
     * Portal Mahasiswa: baris KRS milik mahasiswa yang login. Milik orang
     * lain dijawab 404 — tidak membocorkan bahwa id itu ada.
     */
    public function viewOwn(User $user, KrsItem $krsItem): Response
    {
        return $user->student !== null && $user->student->id === $krsItem->student_id
            ? Response::allow()
            : Response::denyAsNotFound();
    }
}
