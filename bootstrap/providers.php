<?php

use App\Providers\AppServiceProvider;
use App\Providers\AuthServiceProvider;
use App\Providers\ModuleServiceProvider;
use Modules\Academic\Providers\AcademicServiceProvider;
use Modules\Admission\Providers\AdmissionServiceProvider;
use Modules\Finance\Providers\FinanceServiceProvider;
use Modules\HumanResource\Providers\HumanResourceServiceProvider;

return [
    AppServiceProvider::class,
    AuthServiceProvider::class,
    ModuleServiceProvider::class,
    AcademicServiceProvider::class,
    AdmissionServiceProvider::class,
    FinanceServiceProvider::class,
    HumanResourceServiceProvider::class,
];
