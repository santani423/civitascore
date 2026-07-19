<?php

namespace Modules\Finance\Policies;

use App\Models\User;

/** Same permission domain as Invoice — no separate `payments.*` slug needed. */
class PaymentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('invoices.read');
    }
}
