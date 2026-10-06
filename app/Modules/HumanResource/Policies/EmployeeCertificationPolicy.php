<?php

namespace Modules\HumanResource\Policies;

use App\Models\User;
use Modules\HumanResource\Models\EmployeeCertification;

/**
 * Sertifikasi pegawai — satu kelompok izin dengan pelatihan (Pengembangan SDM).
 */
class EmployeeCertificationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('hr_training.read');
    }

    public function view(User $user, EmployeeCertification $record): bool
    {
        return $user->hasPermissionTo('hr_training.read');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('hr_training.create');
    }

    public function update(User $user, EmployeeCertification $record): bool
    {
        return $user->hasPermissionTo('hr_training.update');
    }

    public function delete(User $user, EmployeeCertification $record): bool
    {
        return $user->hasPermissionTo('hr_training.delete');
    }
}
