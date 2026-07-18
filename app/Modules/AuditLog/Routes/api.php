<?php

use Illuminate\Support\Facades\Route;
use Modules\AuditLog\Controllers\AuditLogController;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('audit-logs', [AuditLogController::class, 'index'])->middleware('permission:audit_logs.read');
    Route::get('audit-logs/{auditLog}', [AuditLogController::class, 'show'])->middleware('permission:audit_logs.read');
});
