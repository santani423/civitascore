<?php

use Illuminate\Support\Facades\Route;
use Modules\SystemSetting\Controllers\FeatureFlagController;
use Modules\SystemSetting\Controllers\SystemSettingController;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('system-settings', [SystemSettingController::class, 'index'])->middleware('permission:system_settings.read');
    Route::get('system-settings/{systemSetting}', [SystemSettingController::class, 'show'])->middleware('permission:system_settings.read');
    Route::put('system-settings/{systemSetting}', [SystemSettingController::class, 'update'])->middleware('permission:system_settings.update');

    Route::get('feature-flags', [FeatureFlagController::class, 'index'])->middleware('permission:feature_flags.read');
    Route::put('feature-flags/{featureFlag}', [FeatureFlagController::class, 'update'])->middleware('permission:feature_flags.update');
});
