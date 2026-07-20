<?php

use Illuminate\Support\Facades\Route;
use Modules\Library\Controllers\BookController;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('books', [BookController::class, 'index'])->middleware('permission:books.read');
    Route::get('books/{book}', [BookController::class, 'show'])->middleware('permission:books.read');
});
