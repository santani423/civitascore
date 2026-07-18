<?php

namespace Modules\AuditLog\Policies;

use App\Models\User;

class AuditLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('audit_logs.read');
    }

    public function view(User $user): bool
    {
        return $user->hasPermissionTo('audit_logs.read');
    }
}
