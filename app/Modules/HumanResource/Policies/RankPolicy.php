<?php

namespace Modules\HumanResource\Policies;

use App\Models\User;
use Modules\HumanResource\Models\Rank;

/**
 * Master pangkat/golongan — bagian dari pengelolaan jabatan & kepangkatan (hr_positions.*).
 */
class RankPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('hr_positions.read');
    }

    public function view(User $user, Rank $record): bool
    {
        return $user->hasPermissionTo('hr_positions.read');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('hr_positions.create');
    }

    public function update(User $user, Rank $record): bool
    {
        return $user->hasPermissionTo('hr_positions.update');
    }

    public function delete(User $user, Rank $record): bool
    {
        return $user->hasPermissionTo('hr_positions.delete');
    }
}
