<?php

namespace Modules\HumanResource\Policies;

use App\Models\User;
use Modules\HumanResource\Models\LecturerActivity;

/**
 * Penelitian & pengabdian dosen — bagian data dosen (hr_lecturers.*).
 */
class LecturerActivityPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('hr_lecturers.read');
    }

    public function view(User $user, LecturerActivity $record): bool
    {
        return $user->hasPermissionTo('hr_lecturers.read');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('hr_lecturers.update');
    }

    public function update(User $user, LecturerActivity $record): bool
    {
        return $user->hasPermissionTo('hr_lecturers.update');
    }

    public function delete(User $user, LecturerActivity $record): bool
    {
        return $user->hasPermissionTo('hr_lecturers.update');
    }
}
