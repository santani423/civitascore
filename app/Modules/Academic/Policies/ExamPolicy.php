<?php

namespace Modules\Academic\Policies;

use App\Models\User;

class ExamPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('exams.read');
    }

    /** Satu ability untuk create/update soal & konfigurasi ujian. */
    public function manage(User $user): bool
    {
        return $user->hasPermissionTo('exams.create') || $user->hasPermissionTo('exams.update');
    }

    public function delete(User $user): bool
    {
        return $user->hasPermissionTo('exams.delete');
    }

    public function publish(User $user): bool
    {
        return $user->hasPermissionTo('exams.publish');
    }
}
