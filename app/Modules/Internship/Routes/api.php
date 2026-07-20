<?php

use Illuminate\Support\Facades\Route;
use Modules\Internship\Controllers\InternshipController;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('internships', [InternshipController::class, 'index'])->middleware('permission:internships.read');
    Route::get('internships/{internship}', [InternshipController::class, 'show'])->middleware('permission:internships.read');
});
