<?php

use Illuminate\Support\Facades\Route;
use Modules\Dashboard\Controllers\DashboardController;

// Every authenticated user, tenant or platform — tenant resolution already
// happens globally via `tenant.resolve` (see routes/api.php); the
// controller itself rejects unresolved-tenant requests (see abort_if).
Route::get('dashboard', [DashboardController::class, 'index'])->middleware('auth:sanctum');
