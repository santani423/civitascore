<?php

namespace Modules\HumanResource\Policies;

use App\Models\User;
use Modules\HumanResource\Models\LecturerAcademicRank;

/**
 * Riwayat jabatan akademik dosen.
 */
class LecturerAcademicRankPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('hr_positions.read') || $user->hasPermissionTo('hr_lecturers.read');
    }

    public function view(User $user, LecturerAcademicRank $record): bool
    {
        return $user->hasPermissionTo('hr_positions.read') || $user->hasPermissionTo('hr_lecturers.read');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('hr_positions.create');
    }

    public function update(User $user, LecturerAcademicRank $record): bool
    {
        return $user->hasPermissionTo('hr_positions.update');
    }

    public function delete(User $user, LecturerAcademicRank $record): bool
    {
        return $user->hasPermissionTo('hr_positions.delete');
    }
}
