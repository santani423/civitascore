<?php

namespace Modules\HumanResource\Policies;

use App\Models\User;
use Modules\HumanResource\Models\PerformanceReview;

/**
 * Penilaian kinerja.
 */
class PerformanceReviewPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('hr_performance.read');
    }

    public function view(User $user, PerformanceReview $record): bool
    {
        return $user->hasPermissionTo('hr_performance.read');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('hr_performance.create');
    }

    public function update(User $user, PerformanceReview $record): bool
    {
        return $user->hasPermissionTo('hr_performance.update');
    }

    public function delete(User $user, PerformanceReview $record): bool
    {
        return $user->hasPermissionTo('hr_performance.delete');
    }
}
