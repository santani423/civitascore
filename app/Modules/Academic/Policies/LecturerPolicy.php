<?php

namespace Modules\Academic\Policies;

use App\Models\User;

class LecturerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('lecturers.read');
    }

    public function view(User $user): bool
    {
        return $user->hasPermissionTo('lecturers.read');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('lecturers.create');
    }

    public function update(User $user): bool
    {
        return $user->hasPermissionTo('lecturers.update');
    }

    public function delete(User $user): bool
    {
        return $user->hasPermissionTo('lecturers.delete');
    }
}
