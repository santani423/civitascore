<?php

use Illuminate\Support\Facades\Route;
use Modules\Alumni\Controllers\AlumniController;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('alumni', [AlumniController::class, 'index'])->middleware('permission:alumni.read');
    Route::get('alumni/{alumni}', [AlumniController::class, 'show'])->middleware('permission:alumni.read');
});
