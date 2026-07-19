<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware(['tenant.resolve'])->group(function (): void {
    foreach (File::directories(app_path('Modules')) as $moduleDirectory) {
        $moduleRoutes = $moduleDirectory.'/Routes/api.php';

        if (File::exists($moduleRoutes)) {
            require $moduleRoutes;
        }
    }
});
