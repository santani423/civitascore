<?php

use Illuminate\Support\Facades\Route;
use Modules\Academic\Controllers\AcademicAdministrationController;
use Modules\Academic\Controllers\AcademicCalendarEventController;
use Modules\Academic\Controllers\AttendanceController;
use Modules\Academic\Controllers\ClassSectionController;
use Modules\Academic\Controllers\CourseController;
use Modules\Academic\Controllers\CurriculumController;
use Modules\Academic\Controllers\EmployeeController;
use Modules\Academic\Controllers\ExamAttemptController;
use Modules\Academic\Controllers\ExamController;
use Modules\Academic\Controllers\ExamGradeRangeController;
use Modules\Academic\Controllers\ExamQuestionController;
use Modules\Academic\Controllers\GradeController;
use Modules\Academic\Controllers\KrsItemController;
use Modules\Academic\Controllers\KrsSubmissionController;
use Modules\Academic\Controllers\LecturerController;
use Modules\Academic\Controllers\PublicExamController;
use Modules\Academic\Controllers\QuestionBankController;
use Modules\Academic\Controllers\StudentAcademicRecordController;
use Modules\Academic\Controllers\StudentController;
use Modules\Academic\Controllers\StudentDashboardController;
use Modules\Academic\Controllers\StudentDocumentController;
use Modules\Academic\Controllers\StudentExamController;
use Modules\Academic\Controllers\StudentKrsController;
use Modules\Academic\Controllers\StudentLearningController;
use Modules\Academic\Controllers\StudentProfileController;
use Modules\Academic\Controllers\StudentRequestController;
use Modules\Academic\Controllers\StudentRequestReviewController;
use Modules\Academic\Controllers\TeachingController;
use Modules\Academic\Controllers\StudyProgramController;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('students', [StudentController::class, 'index'])->middleware('permission:students.read');
    Route::post('students', [StudentController::class, 'store'])->middleware('permission:students.create');
    Route::get('students/{student}', [StudentController::class, 'show'])->middleware('permission:students.read');
    Route::get('students/{student}/transcript', [StudentController::class, 'transcript'])->middleware('permission:students.read');

    Route::get('lecturers', [LecturerController::class, 'index'])->middleware('permission:lecturers.read');
    Route::post('lecturers', [LecturerController::class, 'store'])->middleware('permission:lecturers.create');
    Route::get('lecturers/{lecturer}', [LecturerController::class, 'show'])->middleware('permission:lecturers.read');
    Route::put('lecturers/{lecturer}', [LecturerController::class, 'update'])->middleware('permission:lecturers.update');
    Route::delete('lecturers/{lecturer}', [LecturerController::class, 'destroy'])->middleware('permission:lecturers.delete');

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
    Route::patch('exams/{exam}/access-link', [ExamController::class, 'generateAccessLink'])->middleware('permission:exams.update');
    Route::get('exams/{exam}/violations/recent', [ExamController::class, 'recentViolations'])->middleware('permission:exam_attempts.read');
    Route::get('exams/{exam}/recap', [ExamController::class, 'recap'])->middleware('permission:exams.read');
    Route::get('exams/{exam}/recap/export', [ExamController::class, 'exportRecap'])->middleware('permission:exams.read');
    Route::get('exams/{exam}/download', [ExamController::class, 'downloadPdf'])->middleware('permission:exams.read');
    Route::get('exams/{exam}/grade-ranges', [ExamGradeRangeController::class, 'index'])->middleware('permission:exams.read');
    Route::put('exams/{exam}/grade-ranges', [ExamGradeRangeController::class, 'update'])->middleware('permission:exams.update');

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

    Route::get('exams/{exam}/participants', [ExamAttemptController::class, 'index'])->middleware('permission:exam_attempts.read');
    Route::post('krs-items/{krsItem}/exams/{exam}/attempt', [ExamAttemptController::class, 'start'])->middleware('permission:exam_attempts.create');
    Route::put('exam-attempts/{examAttempt}/answer', [ExamAttemptController::class, 'answer'])->middleware('permission:exam_attempts.update');
    Route::patch('exam-attempts/{examAttempt}/submit', [ExamAttemptController::class, 'submit'])->middleware('permission:exam_attempts.update');
    Route::get('exam-attempts/{examAttempt}/violations', [ExamAttemptController::class, 'violations'])->middleware('permission:exam_attempts.read');

    // Portal Mahasiswa — self-service mengerjakan ujian sendiri (terpisah
    // dari exam-attempts di atas, lihat komentar ExamParticipationPolicy).
    Route::get('student/exams', [StudentExamController::class, 'index'])->middleware('permission:exam_participation.read');
    Route::get('student/exams/{exam}', [StudentExamController::class, 'show'])->middleware('permission:exam_participation.read');
    Route::post('student/exams/{exam}/start', [StudentExamController::class, 'start'])->middleware('permission:exam_participation.create');
    Route::get('student/exam-attempts/{examAttempt}', [StudentExamController::class, 'showAttempt'])->middleware('permission:exam_participation.read');
    Route::put('student/exam-attempts/{examAttempt}/answer', [StudentExamController::class, 'answer'])->middleware('permission:exam_participation.update');
    Route::patch('student/exam-attempts/{examAttempt}/submit', [StudentExamController::class, 'submit'])->middleware('permission:exam_participation.update');
    Route::post('student/exam-attempts/{examAttempt}/violations', [StudentExamController::class, 'recordViolation'])->middleware('permission:exam_participation.update');
    Route::get('student/exam-attempts/{examAttempt}/result', [StudentExamController::class, 'result'])->middleware('permission:exam_participation.read');
    Route::get('student/exam-attempts/{examAttempt}/result/pdf', [StudentExamController::class, 'resultPdf'])->middleware('permission:exam_participation.read');

    // Portal Mahasiswa — layanan mandiri. Tidak ada satu pun yang menerima
    // student_id dari klien: mahasiswa selalu diresolusi dari akun yang login
    // (ResolvesCurrentStudent), resource ber-id dicek kepemilikannya lewat policy.
    Route::middleware('permission:student_portal.read')->group(function (): void {
        Route::get('student/dashboard', [StudentDashboardController::class, 'show']);
        Route::get('student/documents', [StudentDashboardController::class, 'documents']);
        Route::get('student/documents/krs', [StudentDocumentController::class, 'krs']);
        Route::get('student/documents/khs/{academicTerm}', [StudentDocumentController::class, 'khs']);
        Route::get('student/documents/transcript', [StudentDocumentController::class, 'transcript']);
        Route::get('student/courses', [StudentLearningController::class, 'courses']);
        Route::get('student/materials', [StudentLearningController::class, 'materials']);
        Route::get('student/courses/{classSection}', [StudentLearningController::class, 'course']);
        Route::get('student/assignments', [StudentLearningController::class, 'assignments']);
        Route::get('student/assignments/{assignment}', [StudentLearningController::class, 'assignment']);
        Route::get('student/profile', [StudentProfileController::class, 'show']);
        Route::get('student/academic-summary', [StudentProfileController::class, 'academicSummary']);
        Route::get('student/academic-history', [StudentProfileController::class, 'history']);
        Route::get('student/schedule', [StudentAcademicRecordController::class, 'schedule']);
        Route::get('student/attendance', [StudentAcademicRecordController::class, 'attendance']);
        Route::get('student/attendance/{krsItem}', [StudentAcademicRecordController::class, 'attendanceDetail']);
        Route::get('student/grades', [StudentAcademicRecordController::class, 'grades']);
        Route::get('student/khs', [StudentAcademicRecordController::class, 'khs']);
        Route::get('student/transcript', [StudentAcademicRecordController::class, 'transcript']);
        Route::get('student/calendar', [StudentAcademicRecordController::class, 'calendar']);
    });
    Route::patch('student/profile', [StudentProfileController::class, 'update'])->middleware('permission:student_portal.update');

    Route::get('student/krs', [StudentKrsController::class, 'show'])->middleware('permission:krs_self_service.read');
    Route::get('student/krs/offerings', [StudentKrsController::class, 'offerings'])->middleware('permission:krs_self_service.read');
    Route::get('student/krs/history', [StudentKrsController::class, 'history'])->middleware('permission:krs_self_service.read');
    Route::post('student/krs/items', [StudentKrsController::class, 'addItem'])->middleware('permission:krs_self_service.create');
    Route::delete('student/krs/items/{krsItem}', [StudentKrsController::class, 'removeItem'])->middleware('permission:krs_self_service.update');
    Route::post('student/krs/submit', [StudentKrsController::class, 'submit'])->middleware('permission:krs_self_service.update');
    Route::post('student/krs/cancel', [StudentKrsController::class, 'cancel'])->middleware('permission:krs_self_service.update');

    Route::post('student/assignments/{assignment}/submission', [StudentLearningController::class, 'submit'])->middleware('permission:assignment_submissions.create');

    Route::get('student/requests', [StudentRequestController::class, 'index'])->middleware('permission:student_requests.read');
    Route::get('student/requests/options', [StudentRequestController::class, 'options'])->middleware('permission:student_requests.read');
    Route::get('student/requests/{studentRequest}', [StudentRequestController::class, 'show'])->middleware('permission:student_requests.read');
    Route::get('student/requests/{studentRequest}/letter', [StudentDocumentController::class, 'letter'])->middleware('permission:student_requests.read');
    Route::post('student/requests', [StudentRequestController::class, 'store'])->middleware('permission:student_requests.create');
    Route::put('student/requests/{studentRequest}', [StudentRequestController::class, 'update'])->middleware('permission:student_requests.update');
    Route::post('student/requests/{studentRequest}/submit', [StudentRequestController::class, 'submit'])->middleware('permission:student_requests.update');
    Route::post('student/requests/{studentRequest}/cancel', [StudentRequestController::class, 'cancel'])->middleware('permission:student_requests.update');

    // Pengajuan mahasiswa — ditinjau Bagian Akademik (keputusan lewat ApprovalWorkflow).
    Route::get('student-requests', [StudentRequestReviewController::class, 'index'])->middleware('permission:approval_requests.read');
    Route::get('student-requests/{studentRequest}', [StudentRequestReviewController::class, 'show'])->middleware('permission:approval_requests.read');
    Route::post('student-requests/{studentRequest}/approve', [StudentRequestReviewController::class, 'approve'])->middleware('permission:approval_requests.read');
    Route::post('student-requests/{studentRequest}/reject', [StudentRequestReviewController::class, 'reject'])->middleware('permission:approval_requests.read');

    // Perkuliahan — dosen pengampu (kelasnya sendiri) & Bagian Akademik.
    Route::get('teaching/classes', [TeachingController::class, 'classes'])->middleware('permission:course_materials.read,assignments.read');
    Route::get('class-sections/{classSection}/materials', [TeachingController::class, 'materials'])->middleware('permission:course_materials.read');
    Route::post('class-sections/{classSection}/materials', [TeachingController::class, 'storeMaterial'])->middleware('permission:course_materials.create');
    Route::put('course-materials/{courseMaterial}', [TeachingController::class, 'updateMaterial'])->middleware('permission:course_materials.update');
    Route::delete('course-materials/{courseMaterial}', [TeachingController::class, 'destroyMaterial'])->middleware('permission:course_materials.delete');
    Route::get('class-sections/{classSection}/assignments', [TeachingController::class, 'assignments'])->middleware('permission:assignments.read');
    Route::post('class-sections/{classSection}/assignments', [TeachingController::class, 'storeAssignment'])->middleware('permission:assignments.create');
    Route::put('assignments/{assignment}', [TeachingController::class, 'updateAssignment'])->middleware('permission:assignments.update');
    Route::delete('assignments/{assignment}', [TeachingController::class, 'destroyAssignment'])->middleware('permission:assignments.delete');
    Route::get('assignments/{assignment}/submissions', [TeachingController::class, 'submissions'])->middleware('permission:assignment_submissions.read');
    Route::put('assignment-submissions/{assignmentSubmission}/grade', [TeachingController::class, 'grade'])->middleware('permission:assignment_submissions.update');

    // Data akademik yang dikonsumsi Portal Mahasiswa — dikelola Bagian Akademik.
    Route::get('academic-terms', [AcademicAdministrationController::class, 'terms'])->middleware('permission:classes.read,krs.read,krs_advising.read,academic_calendar.read,course_materials.read');
    Route::patch('academic-terms/{academicTerm}/krs-period', [AcademicAdministrationController::class, 'updateKrsPeriod'])->middleware('permission:krs.update');
    Route::put('class-sections/{classSection}/teaching', [AcademicAdministrationController::class, 'updateTeaching'])->middleware('permission:classes.update');
    Route::get('courses/{course}/prerequisites', [AcademicAdministrationController::class, 'prerequisites'])->middleware('permission:courses.read');
    Route::put('courses/{course}/prerequisites', [AcademicAdministrationController::class, 'updatePrerequisites'])->middleware('permission:courses.update');
    Route::patch('students/{student}/academic-advisor', [AcademicAdministrationController::class, 'assignAdvisor'])->middleware('permission:students.update');
    Route::get('academic-calendar-events', [AcademicCalendarEventController::class, 'index'])->middleware('permission:academic_calendar.read');
    Route::post('academic-calendar-events', [AcademicCalendarEventController::class, 'store'])->middleware('permission:academic_calendar.create');
    Route::put('academic-calendar-events/{academicCalendarEvent}', [AcademicCalendarEventController::class, 'update'])->middleware('permission:academic_calendar.update');
    Route::delete('academic-calendar-events/{academicCalendarEvent}', [AcademicCalendarEventController::class, 'destroy'])->middleware('permission:academic_calendar.delete');

    // Persetujuan KRS — dosen wali (mahasiswa perwaliannya) & Bagian Akademik.
    Route::get('krs-submissions', [KrsSubmissionController::class, 'index'])->middleware('permission:krs.approve,krs_advising.read');
    Route::get('krs-submissions/{krsSubmission}', [KrsSubmissionController::class, 'show'])->middleware('permission:krs.approve,krs_advising.read');
    Route::post('krs-submissions/{krsSubmission}/approve', [KrsSubmissionController::class, 'approve'])->middleware('permission:krs.approve,krs_advising.approve');
    Route::post('krs-submissions/{krsSubmission}/reject', [KrsSubmissionController::class, 'reject'])->middleware('permission:krs.approve,krs_advising.approve');
});

// Akses ujian publik lewat link/QR + NIM, tanpa login (spec §3-11) — lihat
// komentar PublicExamController. Tetap di bawah `tenant.resolve` (di
// routes/api.php), yang sudah aman untuk guest/tanpa auth.
Route::prefix('public')->group(function () {
    Route::get('exams/{accessToken}', [PublicExamController::class, 'show']);
    Route::post('exams/{accessToken}/access', [PublicExamController::class, 'access'])->middleware('throttle:10,1');
    Route::get('exam-attempts/{sessionToken}', [PublicExamController::class, 'showAttempt']);
    Route::put('exam-attempts/{sessionToken}/answer', [PublicExamController::class, 'answer']);
    Route::patch('exam-attempts/{sessionToken}/submit', [PublicExamController::class, 'submit']);
    Route::post('exam-attempts/{sessionToken}/violations', [PublicExamController::class, 'recordViolation']);
    Route::get('exam-attempts/{sessionToken}/result', [PublicExamController::class, 'result']);
    Route::get('exam-attempts/{sessionToken}/result/pdf', [PublicExamController::class, 'resultPdf']);
});
