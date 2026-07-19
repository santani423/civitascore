<?php

use Illuminate\Support\Facades\Route;
use Modules\Academic\Controllers\ClassSectionController;
use Modules\Academic\Controllers\EmployeeController;
use Modules\Academic\Controllers\LecturerController;
use Modules\Academic\Controllers\StudentController;
use Modules\Academic\Controllers\StudyProgramController;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('students', [StudentController::class, 'index'])->middleware('permission:students.read');
    Route::get('students/{student}', [StudentController::class, 'show'])->middleware('permission:students.read');

    Route::get('lecturers', [LecturerController::class, 'index'])->middleware('permission:lecturers.read');
    Route::get('lecturers/{lecturer}', [LecturerController::class, 'show'])->middleware('permission:lecturers.read');

    Route::get('employees', [EmployeeController::class, 'index'])->middleware('permission:employees.read');
    Route::get('employees/{employee}', [EmployeeController::class, 'show'])->middleware('permission:employees.read');

    Route::get('study-programs', [StudyProgramController::class, 'index'])->middleware('permission:study_programs.read');
    Route::get('study-programs/{studyProgram}', [StudyProgramController::class, 'show'])->middleware('permission:study_programs.read');

    Route::get('class-sections', [ClassSectionController::class, 'index'])->middleware('permission:classes.read');
    Route::get('class-sections/{classSection}', [ClassSectionController::class, 'show'])->middleware('permission:classes.read');
});
