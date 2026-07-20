<?php

use Illuminate\Support\Facades\Route;
use Modules\Thesis\Controllers\ThesisController;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('theses', [ThesisController::class, 'index'])->middleware('permission:theses.read');
    Route::get('theses/{thesis}', [ThesisController::class, 'show'])->middleware('permission:theses.read');
});
