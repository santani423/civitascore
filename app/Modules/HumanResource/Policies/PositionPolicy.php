<?php

namespace Modules\HumanResource\Policies;

use App\Models\User;
use Modules\HumanResource\Models\Position;

/**
 * Master jabatan.
 */
class PositionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('hr_positions.read');
    }

    public function view(User $user, Position $record): bool
    {
        return $user->hasPermissionTo('hr_positions.read');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('hr_positions.create');
    }

    public function update(User $user, Position $record): bool
    {
        return $user->hasPermissionTo('hr_positions.update');
    }

    public function delete(User $user, Position $record): bool
    {
        return $user->hasPermissionTo('hr_positions.delete');
    }
}
