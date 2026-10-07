<?php

namespace Modules\Academic\Policies;

use App\Models\User;
use Modules\Academic\Models\Student;

class StudentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('students.read');
    }

    public function view(User $user): bool
    {
        return $user->hasPermissionTo('students.read');
    }

    public function viewTranscript(User $user, Student $student): bool
    {
        return $user->hasPermissionTo('students.read');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('students.create');
    }

    /** Bagian Akademik: menetapkan dosen wali, dsb. */
    public function update(User $user): bool
    {
        return $user->hasPermissionTo('students.update');
    }
}
