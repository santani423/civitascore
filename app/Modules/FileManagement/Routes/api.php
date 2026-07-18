<?php

use Illuminate\Support\Facades\Route;
use Modules\FileManagement\Controllers\FileUploadController;

Route::middleware('auth:sanctum')->group(function () {
    Route::post('file-uploads', [FileUploadController::class, 'store']);
    Route::get('file-uploads/{fileUpload}', [FileUploadController::class, 'show']);
    Route::get('file-uploads/{fileUpload}/download', [FileUploadController::class, 'download']);
    Route::delete('file-uploads/{fileUpload}', [FileUploadController::class, 'destroy']);
});
