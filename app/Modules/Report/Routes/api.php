<?php

use Illuminate\Support\Facades\Route;
use Modules\Report\Controllers\ReportController;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('reports', [ReportController::class, 'index'])->middleware('permission:reports.read');
    Route::get('reports/{type}', [ReportController::class, 'show'])->middleware('permission:reports.read');
});
