<?php

namespace Modules\Finance\Policies;

use App\Models\User;

class InvoicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('invoices.read');
    }

    public function view(User $user): bool
    {
        return $user->hasPermissionTo('invoices.read');
    }
}
