<?php

namespace Modules\Auth\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Modules\Auth\Models\UserDevice;

class NewDeviceDetected
{
    use Dispatchable;

    public function __construct(
        public readonly User $user,
        public readonly UserDevice $device,
    ) {}
}
