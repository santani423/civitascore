<?php

use Illuminate\Support\Facades\Route;
use Modules\Academic\Controllers\StudentController;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('students', [StudentController::class, 'index'])->middleware('permission:students.read');
    Route::get('students/{student}', [StudentController::class, 'show'])->middleware('permission:students.read');
});
