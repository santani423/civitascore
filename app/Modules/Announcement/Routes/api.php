<?php

use Illuminate\Support\Facades\Route;
use Modules\Announcement\Controllers\AnnouncementController;
use Modules\Announcement\Controllers\StudentAnnouncementController;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('announcements', [AnnouncementController::class, 'index'])->middleware('permission:announcements.read');
    Route::post('announcements', [AnnouncementController::class, 'store'])->middleware('permission:announcements.create');
    Route::get('announcements/{announcement}', [AnnouncementController::class, 'show'])->middleware('permission:announcements.read');
    Route::put('announcements/{announcement}', [AnnouncementController::class, 'update'])->middleware('permission:announcements.update');
    Route::delete('announcements/{announcement}', [AnnouncementController::class, 'destroy'])->middleware('permission:announcements.delete');

    // Portal Mahasiswa — feed pengumuman yang ditujukan kepada mahasiswa login.
    Route::get('student/announcements', [StudentAnnouncementController::class, 'index'])->middleware('permission:student_portal.read');
    Route::get('student/announcements/{announcement}', [StudentAnnouncementController::class, 'show'])->middleware('permission:student_portal.read');
    Route::post('student/announcements/{announcement}/read', [StudentAnnouncementController::class, 'markRead'])->middleware('permission:student_portal.read');
});
