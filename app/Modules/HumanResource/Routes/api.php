<?php

use Illuminate\Support\Facades\Route;
use Modules\HumanResource\Controllers\EmployeeCertificationController;
use Modules\HumanResource\Controllers\EmployeeContractController;
use Modules\HumanResource\Controllers\EmployeeDocumentController;
use Modules\HumanResource\Controllers\EmployeeEducationController;
use Modules\HumanResource\Controllers\EmployeePositionController;
use Modules\HumanResource\Controllers\EmployeeRankController;
use Modules\HumanResource\Controllers\EmployeeTrainingController;
use Modules\HumanResource\Controllers\EmployeeTransferController;
use Modules\HumanResource\Controllers\HrAuditLogController;
use Modules\HumanResource\Controllers\HrDashboardController;
use Modules\HumanResource\Controllers\HrEmployeeController;
use Modules\HumanResource\Controllers\HrOptionsController;
use Modules\HumanResource\Controllers\HrReportController;
use Modules\HumanResource\Controllers\HrRequestController;
use Modules\HumanResource\Controllers\LeaveRequestController;
use Modules\HumanResource\Controllers\LecturerAcademicRankController;
use Modules\HumanResource\Controllers\LecturerActivityController;
use Modules\HumanResource\Controllers\PerformanceReviewController;
use Modules\HumanResource\Controllers\PositionController;
use Modules\HumanResource\Controllers\RankController;
use Modules\HumanResource\Controllers\SelfServiceController;
use Modules\HumanResource\Controllers\WorkUnitController;

/*
| Modul SDM. Data kepegawaian sensitif → selain permission per route,
| seluruh grup memakai tenant.access (keanggotaan aktif di universitas
| wajib), tidak hanya permission yang ter-scope tenant. Policy di
| controller tetap menjadi lapis kedua.
*/

$hrRead = 'permission:hr_dashboard.read,hr_employees.read,hr_lecturers.read,hr_staff.read,hr_positions.read,hr_transfers.read,'
    .'hr_contracts.read,hr_documents.read,hr_leave.read,hr_requests.read,hr_training.read,hr_performance.read,hr_reports.read,hr_audit.read';

Route::prefix('hr')->middleware(['auth:sanctum', 'tenant.access'])->group(function () use ($hrRead): void {
    Route::get('dashboard', [HrDashboardController::class, 'index'])->middleware('permission:hr_dashboard.read');
    Route::get('notifications', [HrDashboardController::class, 'notifications'])->middleware('permission:hr_dashboard.read');
    Route::post('notifications/read', [HrDashboardController::class, 'markNotificationsRead'])->middleware('permission:hr_dashboard.read');
    Route::get('options', [HrOptionsController::class, 'index'])->middleware($hrRead.',hr_self_service.read');

    // Data Pegawai / Dosen / Tenaga Kependidikan
    Route::get('employees', [HrEmployeeController::class, 'index'])->middleware('permission:hr_employees.read');
    Route::get('lecturers', [HrEmployeeController::class, 'lecturers'])->middleware('permission:hr_lecturers.read,hr_employees.read');
    Route::get('staff', [HrEmployeeController::class, 'staff'])->middleware('permission:hr_staff.read,hr_employees.read');
    Route::get('staff/summary', [HrEmployeeController::class, 'staffSummary'])->middleware('permission:hr_staff.read,hr_employees.read');
    Route::get('employees/export', [HrEmployeeController::class, 'export'])->middleware('permission:hr_employees.export');
    Route::get('employees/user-options', [HrEmployeeController::class, 'userOptions'])->middleware('permission:hr_employees.create,hr_employees.update');
    Route::get('employment-statuses', [HrEmployeeController::class, 'statusSummary'])->middleware('permission:hr_employees.read');
    Route::post('employees', [HrEmployeeController::class, 'store'])->middleware('permission:hr_employees.create');
    Route::get('employees/{employee}', [HrEmployeeController::class, 'show'])->middleware('permission:hr_employees.read,hr_lecturers.read,hr_staff.read');
    Route::put('employees/{employee}', [HrEmployeeController::class, 'update'])->middleware('permission:hr_employees.update');
    Route::patch('employees/{employee}/deactivate', [HrEmployeeController::class, 'deactivate'])->middleware('permission:hr_employees.update');
    Route::patch('employees/{employee}/activate', [HrEmployeeController::class, 'activate'])->middleware('permission:hr_employees.update');
    Route::delete('employees/{employee}', [HrEmployeeController::class, 'destroy'])->middleware('permission:hr_employees.delete');
    Route::get('employees/{employee}/audit-logs', [HrAuditLogController::class, 'forEmployee'])->middleware('permission:hr_audit.read');
    Route::get('employees/{employee}/leave-quota', [LeaveRequestController::class, 'quota'])->middleware('permission:hr_leave.read');

    // Riwayat pendidikan
    Route::get('educations', [EmployeeEducationController::class, 'all'])->middleware('permission:hr_employees.read');
    Route::get('employees/{employee}/educations', [EmployeeEducationController::class, 'index'])->middleware('permission:hr_employees.read');
    Route::post('employees/{employee}/educations', [EmployeeEducationController::class, 'store'])->middleware('permission:hr_employees.update');
    Route::put('educations/{education}', [EmployeeEducationController::class, 'update'])->middleware('permission:hr_employees.update');
    Route::delete('educations/{education}', [EmployeeEducationController::class, 'destroy'])->middleware('permission:hr_employees.update');

    // Riwayat jabatan
    Route::get('position-histories', [EmployeePositionController::class, 'all'])->middleware('permission:hr_positions.read');
    Route::get('employees/{employee}/positions', [EmployeePositionController::class, 'index'])->middleware('permission:hr_positions.read,hr_employees.read');
    Route::post('employees/{employee}/positions', [EmployeePositionController::class, 'store'])->middleware('permission:hr_positions.create');
    Route::put('position-histories/{employeePosition}', [EmployeePositionController::class, 'update'])->middleware('permission:hr_positions.update');
    Route::delete('position-histories/{employeePosition}', [EmployeePositionController::class, 'destroy'])->middleware('permission:hr_positions.delete');

    // Kepangkatan
    Route::get('rank-histories', [EmployeeRankController::class, 'all'])->middleware('permission:hr_positions.read');
    Route::get('employees/{employee}/ranks', [EmployeeRankController::class, 'index'])->middleware('permission:hr_positions.read,hr_employees.read');
    Route::post('employees/{employee}/ranks', [EmployeeRankController::class, 'store'])->middleware('permission:hr_positions.create');
    Route::put('rank-histories/{employeeRank}', [EmployeeRankController::class, 'update'])->middleware('permission:hr_positions.update');
    Route::delete('rank-histories/{employeeRank}', [EmployeeRankController::class, 'destroy'])->middleware('permission:hr_positions.delete');

    // Jabatan akademik & penelitian/pengabdian (dosen)
    Route::get('academic-ranks', [LecturerAcademicRankController::class, 'all'])->middleware('permission:hr_positions.read,hr_lecturers.read');
    Route::get('employees/{employee}/academic-ranks', [LecturerAcademicRankController::class, 'index'])->middleware('permission:hr_positions.read,hr_lecturers.read');
    Route::post('employees/{employee}/academic-ranks', [LecturerAcademicRankController::class, 'store'])->middleware('permission:hr_positions.create');
    Route::put('academic-ranks/{lecturerAcademicRank}', [LecturerAcademicRankController::class, 'update'])->middleware('permission:hr_positions.update');
    Route::delete('academic-ranks/{lecturerAcademicRank}', [LecturerAcademicRankController::class, 'destroy'])->middleware('permission:hr_positions.delete');
    Route::get('employees/{employee}/activities', [LecturerActivityController::class, 'index'])->middleware('permission:hr_lecturers.read');
    Route::post('employees/{employee}/activities', [LecturerActivityController::class, 'store'])->middleware('permission:hr_lecturers.update');
    Route::put('lecturer-activities/{lecturerActivity}', [LecturerActivityController::class, 'update'])->middleware('permission:hr_lecturers.update');
    Route::delete('lecturer-activities/{lecturerActivity}', [LecturerActivityController::class, 'destroy'])->middleware('permission:hr_lecturers.update');

    // Master data
    Route::get('work-units', [WorkUnitController::class, 'index'])->middleware('permission:hr_positions.read');
    Route::post('work-units', [WorkUnitController::class, 'store'])->middleware('permission:hr_positions.create');
    Route::put('work-units/{workUnit}', [WorkUnitController::class, 'update'])->middleware('permission:hr_positions.update');
    Route::delete('work-units/{workUnit}', [WorkUnitController::class, 'destroy'])->middleware('permission:hr_positions.delete');
    Route::get('positions', [PositionController::class, 'index'])->middleware('permission:hr_positions.read');
    Route::post('positions', [PositionController::class, 'store'])->middleware('permission:hr_positions.create');
    Route::put('positions/{position}', [PositionController::class, 'update'])->middleware('permission:hr_positions.update');
    Route::delete('positions/{position}', [PositionController::class, 'destroy'])->middleware('permission:hr_positions.delete');
    Route::get('ranks', [RankController::class, 'index'])->middleware('permission:hr_positions.read');
    Route::post('ranks', [RankController::class, 'store'])->middleware('permission:hr_positions.create');
    Route::put('ranks/{rank}', [RankController::class, 'update'])->middleware('permission:hr_positions.update');
    Route::delete('ranks/{rank}', [RankController::class, 'destroy'])->middleware('permission:hr_positions.delete');

    // Kontrak kerja
    Route::get('contracts', [EmployeeContractController::class, 'index'])->middleware('permission:hr_contracts.read');
    Route::post('contracts', [EmployeeContractController::class, 'store'])->middleware('permission:hr_contracts.create');
    Route::put('contracts/{contract}', [EmployeeContractController::class, 'update'])->middleware('permission:hr_contracts.update');
    Route::patch('contracts/{contract}/terminate', [EmployeeContractController::class, 'terminate'])->middleware('permission:hr_contracts.update');
    Route::delete('contracts/{contract}', [EmployeeContractController::class, 'destroy'])->middleware('permission:hr_contracts.delete');

    // Dokumen kepegawaian
    Route::get('documents', [EmployeeDocumentController::class, 'index'])->middleware('permission:hr_documents.read');
    Route::post('documents', [EmployeeDocumentController::class, 'store'])->middleware('permission:hr_documents.create');
    Route::get('documents/{document}/versions', [EmployeeDocumentController::class, 'versions'])->middleware('permission:hr_documents.read');
    Route::put('documents/{document}', [EmployeeDocumentController::class, 'update'])->middleware('permission:hr_documents.update');
    Route::patch('documents/{document}/verify', [EmployeeDocumentController::class, 'verify'])->middleware('permission:hr_documents.update');
    Route::delete('documents/{document}', [EmployeeDocumentController::class, 'destroy'])->middleware('permission:hr_documents.delete');

    // Pengembangan SDM
    Route::get('trainings', [EmployeeTrainingController::class, 'index'])->middleware('permission:hr_training.read');
    Route::post('trainings', [EmployeeTrainingController::class, 'store'])->middleware('permission:hr_training.create');
    Route::put('trainings/{training}', [EmployeeTrainingController::class, 'update'])->middleware('permission:hr_training.update');
    Route::delete('trainings/{training}', [EmployeeTrainingController::class, 'destroy'])->middleware('permission:hr_training.delete');
    Route::get('certifications', [EmployeeCertificationController::class, 'index'])->middleware('permission:hr_training.read');
    Route::post('certifications', [EmployeeCertificationController::class, 'store'])->middleware('permission:hr_training.create');
    Route::put('certifications/{certification}', [EmployeeCertificationController::class, 'update'])->middleware('permission:hr_training.update');
    Route::delete('certifications/{certification}', [EmployeeCertificationController::class, 'destroy'])->middleware('permission:hr_training.delete');
    Route::get('performance-reviews', [PerformanceReviewController::class, 'index'])->middleware('permission:hr_performance.read');
    Route::post('performance-reviews', [PerformanceReviewController::class, 'store'])->middleware('permission:hr_performance.create');
    Route::put('performance-reviews/{performanceReview}', [PerformanceReviewController::class, 'update'])->middleware('permission:hr_performance.update');
    Route::delete('performance-reviews/{performanceReview}', [PerformanceReviewController::class, 'destroy'])->middleware('permission:hr_performance.delete');

    // Penempatan & mutasi
    Route::get('transfers', [EmployeeTransferController::class, 'index'])->middleware('permission:hr_transfers.read');
    Route::post('transfers', [EmployeeTransferController::class, 'store'])->middleware('permission:hr_transfers.create');
    Route::patch('transfers/{transfer}/cancel', [EmployeeTransferController::class, 'cancel'])->middleware('permission:hr_transfers.update');

    // Cuti & izin
    Route::get('leave-requests', [LeaveRequestController::class, 'index'])->middleware('permission:hr_leave.read');
    Route::post('leave-requests', [LeaveRequestController::class, 'store'])->middleware('permission:hr_leave.create');
    Route::get('leave-requests/{leaveRequest}', [LeaveRequestController::class, 'show'])->middleware('permission:hr_leave.read');
    Route::post('leave-requests/{leaveRequest}/submit', [LeaveRequestController::class, 'submit'])->middleware('permission:hr_leave.create');
    Route::post('leave-requests/{leaveRequest}/approve', [LeaveRequestController::class, 'approve'])->middleware('permission:hr_leave.approve');
    Route::post('leave-requests/{leaveRequest}/reject', [LeaveRequestController::class, 'reject'])->middleware('permission:hr_leave.reject');
    Route::patch('leave-requests/{leaveRequest}/cancel', [LeaveRequestController::class, 'cancel'])->middleware('permission:hr_leave.create');

    // Pengajuan & persetujuan
    Route::get('requests', [HrRequestController::class, 'index'])->middleware('permission:hr_requests.read');
    Route::get('approvals', [HrRequestController::class, 'approvals'])->middleware('permission:hr_requests.approve,hr_leave.approve');
    Route::post('requests', [HrRequestController::class, 'store'])->middleware('permission:hr_requests.create');
    Route::get('requests/{hrRequest}', [HrRequestController::class, 'show'])->middleware('permission:hr_requests.read,hr_leave.read');
    Route::post('requests/{hrRequest}/approve', [HrRequestController::class, 'approve'])->middleware('permission:hr_requests.approve,hr_leave.approve');
    Route::post('requests/{hrRequest}/reject', [HrRequestController::class, 'reject'])->middleware('permission:hr_requests.reject,hr_leave.reject');
    Route::patch('requests/{hrRequest}/process', [HrRequestController::class, 'process'])->middleware('permission:hr_requests.update');
    Route::patch('requests/{hrRequest}/cancel', [HrRequestController::class, 'cancel'])->middleware('permission:hr_requests.update');

    // Laporan & audit
    Route::get('reports', [HrReportController::class, 'index'])->middleware('permission:hr_reports.read');
    Route::get('reports/{type}', [HrReportController::class, 'show'])->middleware('permission:hr_reports.read');
    Route::get('audit-logs', [HrAuditLogController::class, 'index'])->middleware('permission:hr_audit.read');
    Route::get('audit-logs/modules', [HrAuditLogController::class, 'modules'])->middleware('permission:hr_audit.read');
});

// Layanan mandiri pegawai & dosen — terpisah dari menu SDM.
Route::prefix('me/hr')->middleware(['auth:sanctum', 'tenant.access'])->group(function (): void {
    Route::get('profile', [SelfServiceController::class, 'profile'])->middleware('permission:hr_self_service.read');
    Route::get('leave-requests', [SelfServiceController::class, 'leaveRequests'])->middleware('permission:hr_self_service.read');
    Route::post('leave-requests', [SelfServiceController::class, 'storeLeaveRequest'])->middleware('permission:hr_self_service.create');
    Route::post('leave-requests/{leaveRequest}/submit', [SelfServiceController::class, 'submitLeaveRequest'])->middleware('permission:hr_self_service.update');
    Route::patch('leave-requests/{leaveRequest}/cancel', [SelfServiceController::class, 'cancelLeaveRequest'])->middleware('permission:hr_self_service.update');
    Route::get('requests', [SelfServiceController::class, 'requests'])->middleware('permission:hr_self_service.read');
    Route::post('requests', [SelfServiceController::class, 'storeRequest'])->middleware('permission:hr_self_service.create');
    Route::patch('requests/{hrRequest}/cancel', [SelfServiceController::class, 'cancelRequest'])->middleware('permission:hr_self_service.update');
});
