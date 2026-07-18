<?php

use Illuminate\Support\Facades\Route;
use Modules\Notification\Controllers\NotificationChannelController;
use Modules\Notification\Controllers\NotificationTemplateController;
use Modules\Notification\Controllers\UserNotificationPreferenceController;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('notification-templates', [NotificationTemplateController::class, 'index'])->middleware('permission:notification_templates.read');
    Route::post('notification-templates', [NotificationTemplateController::class, 'store'])->middleware('permission:notification_templates.create');
    Route::put('notification-templates/{notificationTemplate}', [NotificationTemplateController::class, 'update'])->middleware('permission:notification_templates.update');

    Route::get('notification-channels', [NotificationChannelController::class, 'index'])->middleware('permission:notification_channels.read');
    Route::put('notification-channels/{notificationChannel}', [NotificationChannelController::class, 'update'])->middleware('permission:notification_channels.update');

    Route::get('notification-preferences', [UserNotificationPreferenceController::class, 'index']);
    Route::post('notification-preferences', [UserNotificationPreferenceController::class, 'store']);
});
