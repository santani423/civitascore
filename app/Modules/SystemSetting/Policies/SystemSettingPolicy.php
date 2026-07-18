<?php

namespace Modules\SystemSetting\Policies;

use App\Models\User;
use Modules\SystemSetting\Models\SystemSetting;

class SystemSettingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('system_settings.read');
    }

    public function view(User $user, SystemSetting $setting): bool
    {
        return $setting->is_public || $user->hasPermissionTo('system_settings.read');
    }

    public function update(User $user, SystemSetting $setting): bool
    {
        return $user->hasPermissionTo('system_settings.update');
    }
}
