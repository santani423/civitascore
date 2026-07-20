<?php

use Illuminate\Support\Facades\Route;
use Modules\Scholarship\Controllers\ScholarshipApplicationController;
use Modules\Scholarship\Controllers\ScholarshipController;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('scholarships', [ScholarshipController::class, 'index'])->middleware('permission:scholarships.read');
    Route::get('scholarships/{scholarship}', [ScholarshipController::class, 'show'])->middleware('permission:scholarships.read');

    Route::get('scholarship-applications', [ScholarshipApplicationController::class, 'index'])->middleware('permission:scholarships.read');
});
