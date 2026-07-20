<?php

use Illuminate\Support\Facades\Route;
use Modules\Announcement\Controllers\AnnouncementController;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('announcements', [AnnouncementController::class, 'index'])->middleware('permission:announcements.read');
    Route::get('announcements/{announcement}', [AnnouncementController::class, 'show'])->middleware('permission:announcements.read');
});
