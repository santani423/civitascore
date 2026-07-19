<?php

use Illuminate\Support\Facades\Route;
use Modules\Tenancy\Controllers\PlatformStatisticsController;
use Modules\Tenancy\Controllers\SupportSessionController;
use Modules\Tenancy\Controllers\TenantProfileController;
use Modules\Tenancy\Controllers\UniversityController;
use Modules\Tenancy\Controllers\UniversitySettingController;

// Platform (Super Admin) — cross-tenant by design, so NOT behind
// tenant.access; gated purely by permission slug (platform_universities.*,
// platform_statistics.read), which only the super_admin role holds.
Route::prefix('platform')->middleware('auth:sanctum')->group(function (): void {
    Route::get('universities', [UniversityController::class, 'index']);
    Route::post('universities', [UniversityController::class, 'store']);
    Route::get('universities/{university}', [UniversityController::class, 'show']);
    Route::patch('universities/{university}', [UniversityController::class, 'update']);
    Route::post('universities/{university}/activate', [UniversityController::class, 'activate']);
    Route::post('universities/{university}/suspend', [UniversityController::class, 'suspend']);

    Route::get('statistics', [PlatformStatisticsController::class, 'index']);

    Route::get('support-sessions', [SupportSessionController::class, 'index']);
    Route::post('support-sessions', [SupportSessionController::class, 'store']);
    Route::post('support-sessions/{supportSession}/end', [SupportSessionController::class, 'end']);
});

// Tenant self-service — always scoped to the caller's resolved university.
Route::prefix('tenant')->middleware(['auth:sanctum', 'tenant.access'])->group(function (): void {
    Route::get('profile', [TenantProfileController::class, 'show']);
    Route::get('settings', [UniversitySettingController::class, 'index']);
    Route::put('settings', [UniversitySettingController::class, 'upsert']);
});
