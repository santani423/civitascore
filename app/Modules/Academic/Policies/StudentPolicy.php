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
}
