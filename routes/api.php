<?php

use App\Http\Controllers\XenditWebhookController;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware(['tenant.resolve'])->group(function (): void {
    // URL callback "Invoices paid" di Dashboard Xendit (dipakai bersama PMB & KEU).
    Route::post('webhooks/xendit', XenditWebhookController::class)->middleware('throttle:600,1');

    foreach (File::directories(app_path('Modules')) as $moduleDirectory) {
        $moduleRoutes = $moduleDirectory.'/Routes/api.php';

        if (File::exists($moduleRoutes)) {
            require $moduleRoutes;
        }
    }
});
