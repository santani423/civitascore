<?php

use Illuminate\Support\Facades\Route;
use Modules\Academic\Controllers\AttendanceController;
use Modules\Academic\Controllers\ClassSectionController;
use Modules\Academic\Controllers\CourseController;
use Modules\Academic\Controllers\CurriculumController;
use Modules\Academic\Controllers\EmployeeController;
use Modules\Academic\Controllers\ExamAttemptController;
use Modules\Academic\Controllers\ExamController;
use Modules\Academic\Controllers\ExamQuestionController;
use Modules\Academic\Controllers\GradeController;
use Modules\Academic\Controllers\KrsItemController;
use Modules\Academic\Controllers\LecturerController;
use Modules\Academic\Controllers\QuestionBankController;
use Modules\Academic\Controllers\StudentController;
use Modules\Academic\Controllers\StudentExamController;
use Modules\Academic\Controllers\StudyProgramController;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('students', [StudentController::class, 'index'])->middleware('permission:students.read');
    Route::get('students/{student}', [StudentController::class, 'show'])->middleware('permission:students.read');
    Route::get('students/{student}/transcript', [StudentController::class, 'transcript'])->middleware('permission:students.read');

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
    Route::post('krs-items', [KrsItemController::class, 'store'])->middleware('permission:krs.create');
    Route::patch('krs-items/{krsItem}/drop', [KrsItemController::class, 'drop'])->middleware('permission:krs.update');

    Route::get('grades', [GradeController::class, 'index'])->middleware('permission:grades.read');
    Route::put('krs-items/{krsItem}/grade', [GradeController::class, 'upsert'])->middleware('permission:grades.create,grades.update');

    Route::get('attendances', [AttendanceController::class, 'index'])->middleware('permission:attendance.read');
    Route::post('class-sections/{classSection}/attendances', [AttendanceController::class, 'batchStore'])->middleware('permission:attendance.create,attendance.update');

    Route::get('exams', [ExamController::class, 'index'])->middleware('permission:exams.read');
    Route::post('exams', [ExamController::class, 'store'])->middleware('permission:exams.create');
    Route::get('exams/{exam}', [ExamController::class, 'show'])->middleware('permission:exams.read');
    Route::put('exams/{exam}', [ExamController::class, 'update'])->middleware('permission:exams.update');
    Route::delete('exams/{exam}', [ExamController::class, 'destroy'])->middleware('permission:exams.delete');
    Route::patch('exams/{exam}/publish', [ExamController::class, 'publish'])->middleware('permission:exams.publish');

    Route::get('exams/{exam}/questions', [ExamQuestionController::class, 'index'])->middleware('permission:exams.read');
    Route::post('exams/{exam}/questions', [ExamQuestionController::class, 'store'])->middleware('permission:exams.create,exams.update');
    Route::put('exam-questions/{examQuestion}', [ExamQuestionController::class, 'update'])->middleware('permission:exams.update');
    Route::delete('exam-questions/{examQuestion}', [ExamQuestionController::class, 'destroy'])->middleware('permission:exams.update,exams.delete');
    Route::post('exams/{exam}/questions/apply-bank', [ExamQuestionController::class, 'applyBank'])->middleware('permission:exams.create,exams.update');

    Route::get('question-bank', [QuestionBankController::class, 'index'])->middleware('permission:question_bank.read');
    Route::post('question-bank', [QuestionBankController::class, 'store'])->middleware('permission:question_bank.create');
    Route::get('question-bank/{questionBankItem}', [QuestionBankController::class, 'show'])->middleware('permission:question_bank.read');
    Route::put('question-bank/{questionBankItem}', [QuestionBankController::class, 'update'])->middleware('permission:question_bank.update');
    Route::delete('question-bank/{questionBankItem}', [QuestionBankController::class, 'destroy'])->middleware('permission:question_bank.delete');

    Route::post('krs-items/{krsItem}/exams/{exam}/attempt', [ExamAttemptController::class, 'start'])->middleware('permission:exam_attempts.create');
    Route::put('exam-attempts/{examAttempt}/answer', [ExamAttemptController::class, 'answer'])->middleware('permission:exam_attempts.update');
    Route::patch('exam-attempts/{examAttempt}/submit', [ExamAttemptController::class, 'submit'])->middleware('permission:exam_attempts.update');

    // Portal Mahasiswa — self-service mengerjakan ujian sendiri (terpisah
    // dari exam-attempts di atas, lihat komentar ExamParticipationPolicy).
    Route::get('student/exams', [StudentExamController::class, 'index'])->middleware('permission:exam_participation.read');
    Route::get('student/exams/{exam}', [StudentExamController::class, 'show'])->middleware('permission:exam_participation.read');
    Route::post('student/exams/{exam}/start', [StudentExamController::class, 'start'])->middleware('permission:exam_participation.create');
    Route::get('student/exam-attempts/{examAttempt}', [StudentExamController::class, 'showAttempt'])->middleware('permission:exam_participation.read');
    Route::put('student/exam-attempts/{examAttempt}/answer', [StudentExamController::class, 'answer'])->middleware('permission:exam_participation.update');
    Route::patch('student/exam-attempts/{examAttempt}/submit', [StudentExamController::class, 'submit'])->middleware('permission:exam_participation.update');
});
