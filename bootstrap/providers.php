<?php

use App\Providers\AppServiceProvider;
use App\Providers\AuthServiceProvider;
use App\Providers\ModuleServiceProvider;
use Modules\HumanResource\Providers\HumanResourceServiceProvider;

return [
    AppServiceProvider::class,
    AuthServiceProvider::class,
    ModuleServiceProvider::class,
    HumanResourceServiceProvider::class,
];
