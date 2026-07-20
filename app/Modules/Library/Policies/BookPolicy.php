<?php

namespace Modules\Library\Policies;

use App\Models\User;

class BookPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('books.read');
    }

    public function view(User $user): bool
    {
        return $user->hasPermissionTo('books.read');
    }
}
