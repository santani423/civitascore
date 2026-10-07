<?php

namespace Modules\HumanResource\Policies;

use App\Models\User;
use Modules\HumanResource\Models\EmployeeTraining;

/**
 * Pelatihan pegawai.
 */
class EmployeeTrainingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('hr_training.read');
    }

    public function view(User $user, EmployeeTraining $record): bool
    {
        return $user->hasPermissionTo('hr_training.read');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('hr_training.create');
    }

    public function update(User $user, EmployeeTraining $record): bool
    {
        return $user->hasPermissionTo('hr_training.update');
    }

    public function delete(User $user, EmployeeTraining $record): bool
    {
        return $user->hasPermissionTo('hr_training.delete');
    }
}
