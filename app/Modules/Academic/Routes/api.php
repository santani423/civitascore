<?php

use Illuminate\Support\Facades\Route;
use Modules\Academic\Controllers\AttendanceController;
use Modules\Academic\Controllers\ClassSectionController;
use Modules\Academic\Controllers\CourseController;
use Modules\Academic\Controllers\CurriculumController;
use Modules\Academic\Controllers\EmployeeController;
use Modules\Academic\Controllers\GradeController;
use Modules\Academic\Controllers\KrsItemController;
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

    Route::get('curriculums', [CurriculumController::class, 'index'])->middleware('permission:curriculums.read');
    Route::get('curriculums/{curriculum}', [CurriculumController::class, 'show'])->middleware('permission:curriculums.read');

    Route::get('courses', [CourseController::class, 'index'])->middleware('permission:courses.read');
    Route::get('courses/{course}', [CourseController::class, 'show'])->middleware('permission:courses.read');

    Route::get('krs-items', [KrsItemController::class, 'index'])->middleware('permission:krs.read');

    Route::get('grades', [GradeController::class, 'index'])->middleware('permission:grades.read');

    Route::get('attendances', [AttendanceController::class, 'index'])->middleware('permission:attendance.read');
});
