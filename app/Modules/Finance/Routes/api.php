<?php

use Illuminate\Support\Facades\Route;
use Modules\Finance\Controllers\InvoiceController;
use Modules\Finance\Controllers\PaymentController;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('invoices', [InvoiceController::class, 'index'])->middleware('permission:invoices.read');
    Route::get('invoices/{invoice}', [InvoiceController::class, 'show'])->middleware('permission:invoices.read');

    Route::get('payments', [PaymentController::class, 'index'])->middleware('permission:invoices.read');
});
