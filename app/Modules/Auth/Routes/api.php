<?php

use Illuminate\Support\Facades\Route;
use Modules\Auth\Controllers\AuthController;
use Modules\Auth\Controllers\DeviceController;
use Modules\Auth\Controllers\SessionController;

Route::get('login', [AuthController::class, 'login']);
Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('reset-password', [AuthController::class, 'resetPassword']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('me', [AuthController::class, 'me']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('logout-all', [AuthController::class, 'logoutAllDevices']);

    Route::get('sessions', [SessionController::class, 'index']);
    Route::delete('sessions/{session}', [SessionController::class, 'destroy']);

    Route::get('devices', [DeviceController::class, 'index']);
});
