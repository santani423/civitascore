<?php

use Illuminate\Support\Facades\Route;
use Modules\Finance\Controllers\Admin\AidController;
use Modules\Finance\Controllers\Admin\BillingController;
use Modules\Finance\Controllers\Admin\ClearanceController;
use Modules\Finance\Controllers\Admin\DashboardController;
use Modules\Finance\Controllers\Admin\DepositRefundController;
use Modules\Finance\Controllers\Admin\PolicyController;
use Modules\Finance\Controllers\Admin\ReconciliationController;
use Modules\Finance\Controllers\Admin\ReportController;
use Modules\Finance\Controllers\Admin\SettingsController;
use Modules\Finance\Controllers\Admin\StudentAccountController;
use Modules\Finance\Controllers\Admin\TariffController;
use Modules\Finance\Controllers\Admin\TransactionController;
use Modules\Finance\Controllers\InvoiceController;
use Modules\Finance\Controllers\PaymentController;
use Modules\Finance\Controllers\ReceiptController;
use Modules\Finance\Controllers\Student\StudentFinanceController;
use Modules\Finance\Controllers\WebhookController;
use Modules\Finance\Support\KeuPermission;

/*
| Modul KEU — Administrasi Keuangan Mahasiswa (docs/keu/catatan-implementasi.md).
| `GET /invoices`, `GET /invoices/{id}`, `GET /payments` sudah ada sebelum KEU
| dan dipertahankan. Endpoint admin baru di bawah `/finance`, layanan mandiri
| mahasiswa di `/student/finance`. Callback penyedia pembayaran publik.
*/

Route::post('finance/webhooks/payments/{provider}', WebhookController::class)->middleware('throttle:120,1');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('invoices', [InvoiceController::class, 'index'])->middleware('permission:invoices.read');
    Route::get('invoices/{invoice}', [InvoiceController::class, 'show'])->middleware('permission:invoices.read');
    Route::get('payments', [PaymentController::class, 'index'])->middleware('permission:invoices.read');
});

Route::middleware(['auth:sanctum', 'tenant.access'])->prefix('finance')->group(function () {
    Route::get('dashboard', [DashboardController::class, 'dashboard'])->middleware('permission:finance_dashboard.read');
    // Data rujukan (semester, prodi, komponen, enum) — untuk setiap pemegang izin baca KEU.
    Route::get('lookups', [DashboardController::class, 'lookups'])->middleware('permission:'.implode(',', KeuPermission::reads()));

    // Komponen biaya & tarif
    Route::get('fee-components', [TariffController::class, 'components'])->middleware('permission:fee_components.read');
    Route::post('fee-components', [TariffController::class, 'storeComponent'])->middleware('permission:fee_components.create');
    Route::put('fee-components/{feeComponent}', [TariffController::class, 'updateComponent'])->middleware('permission:fee_components.update');
    Route::delete('fee-components/{feeComponent}', [TariffController::class, 'destroyComponent'])->middleware('permission:fee_components.delete');
    Route::get('fee-rate-tables', [TariffController::class, 'tables'])->middleware('permission:fee_rates.read');
    Route::post('fee-rate-tables', [TariffController::class, 'storeTable'])->middleware('permission:fee_rates.create');
    Route::get('fee-rate-tables/{feeRateTable}', [TariffController::class, 'showTable'])->middleware('permission:fee_rates.read');
    Route::post('fee-rate-tables/{feeRateTable}/rates', [TariffController::class, 'storeRate'])->middleware('permission:fee_rates.update');
    Route::put('fee-rates/{feeRate}', [TariffController::class, 'updateRate'])->middleware('permission:fee_rates.update');
    Route::delete('fee-rates/{feeRate}', [TariffController::class, 'destroyRate'])->middleware('permission:fee_rates.update');
    Route::post('fee-rate-tables/{feeRateTable}/submit', [TariffController::class, 'submit'])->middleware('permission:fee_rates.update');
    Route::post('fee-rate-tables/{feeRateTable}/approve', [TariffController::class, 'approve'])->middleware('permission:fee_rates.approve');
    Route::post('fee-rate-tables/{feeRateTable}/reject', [TariffController::class, 'reject'])->middleware('permission:fee_rates.approve');
    Route::post('tariff-preview', [TariffController::class, 'preview'])->middleware('permission:fee_rates.read');

    // Tagihan
    Route::post('invoices', [InvoiceController::class, 'store'])->middleware('permission:invoices.create');
    Route::post('invoices/{invoice}/issue', [InvoiceController::class, 'issue'])->middleware('permission:invoices.create');
    Route::delete('invoices/{invoice}', [InvoiceController::class, 'destroyDraft'])->middleware('permission:invoices.update');
    Route::get('invoices-export', [InvoiceController::class, 'export'])->middleware('permission:invoices.export');
    Route::get('billing-runs', [BillingController::class, 'runs'])->middleware('permission:billing_runs.read');
    Route::post('billing-runs', [BillingController::class, 'simulate'])->middleware('permission:billing_runs.create');
    Route::get('billing-runs/{billingRun}', [BillingController::class, 'showRun'])->middleware('permission:billing_runs.read');
    Route::post('billing-runs/{billingRun}/submit', [BillingController::class, 'submit'])->middleware('permission:billing_runs.create');
    Route::post('billing-runs/{billingRun}/approve', [BillingController::class, 'approve'])->middleware('permission:billing_runs.approve');
    Route::post('billing-runs/{billingRun}/reject', [BillingController::class, 'reject'])->middleware('permission:billing_runs.approve');
    Route::post('billing-runs/{billingRun}/cancel', [BillingController::class, 'cancel'])->middleware('permission:billing_runs.create');
    Route::post('credit-billing', [BillingController::class, 'billCredits'])->middleware('permission:invoices.create');
    Route::get('adjustments', [BillingController::class, 'adjustments'])->middleware('permission:invoice_adjustments.read');
    Route::post('invoices/{invoice}/adjustments', [BillingController::class, 'requestAdjustment'])->middleware('permission:invoice_adjustments.create');
    Route::post('adjustments/{invoiceAdjustment}/approve', [BillingController::class, 'approveAdjustment'])->middleware('permission:invoice_adjustments.approve');
    Route::post('adjustments/{invoiceAdjustment}/reject', [BillingController::class, 'rejectAdjustment'])->middleware('permission:invoice_adjustments.approve');
    Route::post('adjustments/{invoiceAdjustment}/cancel', [BillingController::class, 'cancelAdjustment'])->middleware('permission:invoice_adjustments.create');

    // Transaksi, bukti transfer, kasir
    Route::get('transactions', [TransactionController::class, 'index'])->middleware('permission:payment_transactions.read');
    Route::get('transactions/{paymentTransaction}', [TransactionController::class, 'show'])->middleware('permission:payment_transactions.read');
    Route::get('transactions/{paymentTransaction}/receipt', [ReceiptController::class, 'show'])->middleware('permission:payment_transactions.read');
    Route::post('transactions/{paymentTransaction}/recheck', [TransactionController::class, 'recheck'])->middleware('permission:payment_transactions.update');
    Route::post('transactions/{paymentTransaction}/cancel', [TransactionController::class, 'cancel'])->middleware('permission:payment_transactions.update');
    Route::post('transactions/{paymentTransaction}/reverse', [TransactionController::class, 'reverse'])->middleware('permission:payment_transactions.approve');
    Route::get('payment-proofs', [TransactionController::class, 'proofs'])->middleware('permission:payment_transactions.read');
    Route::post('payment-proofs/{paymentProof}/approve', [TransactionController::class, 'approveProof'])->middleware('permission:payment_transactions.approve');
    Route::post('payment-proofs/{paymentProof}/reject', [TransactionController::class, 'rejectProof'])->middleware('permission:payment_transactions.approve');
    Route::get('cashier-sessions', [TransactionController::class, 'sessions'])->middleware('permission:cashier_sessions.read');
    Route::post('cashier-sessions', [TransactionController::class, 'openSession'])->middleware('permission:cashier_sessions.create');
    Route::post('cashier-sessions/{cashierSession}/payments', [TransactionController::class, 'recordCash'])->middleware('permission:payment_transactions.create');
    Route::post('cashier-sessions/{cashierSession}/close', [TransactionController::class, 'closeSession'])->middleware('permission:cashier_sessions.create');
    Route::post('cashier-sessions/{cashierSession}/deposit', [TransactionController::class, 'depositSession'])->middleware('permission:cashier_sessions.create');
    Route::post('cashier-sessions/{cashierSession}/verify', [TransactionController::class, 'verifySession'])->middleware('permission:cashier_sessions.approve');

    // Deposit & refund
    Route::get('deposits', [DepositRefundController::class, 'accounts'])->middleware('permission:student_deposits.read');
    Route::get('deposits/{student}', [DepositRefundController::class, 'ledger'])->middleware('permission:student_deposits.read');
    Route::post('deposits/apply', [DepositRefundController::class, 'apply'])->middleware('permission:student_deposits.update');
    Route::get('refunds', [DepositRefundController::class, 'refunds'])->middleware('permission:refunds.read');
    Route::post('refunds', [DepositRefundController::class, 'storeRefund'])->middleware('permission:refunds.create');
    Route::get('refunds/{refund}', [DepositRefundController::class, 'showRefund'])->middleware('permission:refunds.read');
    Route::post('refunds/{refund}/approve', [DepositRefundController::class, 'approveRefund'])->middleware('permission:refunds.approve');
    Route::post('refunds/{refund}/reject', [DepositRefundController::class, 'rejectRefund'])->middleware('permission:refunds.approve');
    Route::post('refunds/{refund}/complete', [DepositRefundController::class, 'completeRefund'])->middleware('permission:refunds.finalize');
    Route::post('refunds/{refund}/fail', [DepositRefundController::class, 'failRefund'])->middleware('permission:refunds.finalize');
    Route::post('refunds/{refund}/cancel', [DepositRefundController::class, 'cancelRefund'])->middleware('permission:refunds.create');

    // Rekonsiliasi
    Route::get('reconciliations', [ReconciliationController::class, 'index'])->middleware('permission:reconciliations.read');
    Route::post('reconciliations', [ReconciliationController::class, 'store'])->middleware('permission:reconciliations.create');
    Route::get('reconciliations/{reconciliationBatch}', [ReconciliationController::class, 'show'])->middleware('permission:reconciliations.read');
    Route::post('reconciliations/{reconciliationBatch}/import', [ReconciliationController::class, 'import'])->middleware('permission:reconciliations.update');
    Route::post('reconciliation-items/{reconciliationItem}/resolve', [ReconciliationController::class, 'resolve'])->middleware('permission:reconciliations.update');
    Route::post('reconciliations/{reconciliationBatch}/close', [ReconciliationController::class, 'close'])->middleware('permission:reconciliations.finalize');

    // Beasiswa, potongan, voucher
    Route::get('discount-policies', [AidController::class, 'policies'])->middleware('permission:financial_aid.read');
    Route::post('discount-policies', [AidController::class, 'storePolicy'])->middleware('permission:financial_aid.create');
    Route::put('discount-policies/{discountPolicy}', [AidController::class, 'updatePolicy'])->middleware('permission:financial_aid.update');
    Route::get('aid-awards', [AidController::class, 'awards'])->middleware('permission:financial_aid.read');
    Route::post('aid-awards', [AidController::class, 'storeAward'])->middleware('permission:financial_aid.create');
    Route::post('aid-awards/{financialAidAward}/approve', [AidController::class, 'approveAward'])->middleware('permission:financial_aid.approve');
    Route::post('aid-awards/{financialAidAward}/reject', [AidController::class, 'rejectAward'])->middleware('permission:financial_aid.approve');
    Route::post('aid-awards/{financialAidAward}/revoke', [AidController::class, 'revokeAward'])->middleware('permission:financial_aid.approve');
    Route::post('aid-awards/{financialAidAward}/apply', [AidController::class, 'applyAward'])->middleware('permission:invoice_adjustments.create');
    Route::get('vouchers', [AidController::class, 'vouchers'])->middleware('permission:vouchers.read');
    Route::post('vouchers', [AidController::class, 'storeVoucher'])->middleware('permission:vouchers.create');
    Route::put('vouchers/{voucher}', [AidController::class, 'updateVoucher'])->middleware('permission:vouchers.update');
    Route::get('vouchers/{voucher}/redemptions', [AidController::class, 'redemptions'])->middleware('permission:vouchers.read');
    Route::post('invoices/{invoice}/voucher', [AidController::class, 'redeemForStudent'])->middleware('permission:vouchers.update');
    Route::post('voucher-redemptions/{voucherRedemption}/reverse', [AidController::class, 'reverseRedemption'])->middleware('permission:vouchers.update');

    // Denda, UKT, cicilan
    Route::get('penalty-rules', [PolicyController::class, 'penaltyRules'])->middleware('permission:penalties.read');
    Route::post('penalty-rules', [PolicyController::class, 'storePenaltyRule'])->middleware('permission:penalties.create');
    Route::put('penalty-rules/{penaltyRule}', [PolicyController::class, 'updatePenaltyRule'])->middleware('permission:penalties.update');
    Route::post('penalty-rules/{penaltyRule}/submit', [PolicyController::class, 'submitPenaltyRule'])->middleware('permission:penalties.update');
    Route::post('penalty-rules/{penaltyRule}/approve', [PolicyController::class, 'approvePenaltyRule'])->middleware('permission:penalties.approve');
    Route::post('penalty-rules/{penaltyRule}/deactivate', [PolicyController::class, 'deactivatePenaltyRule'])->middleware('permission:penalties.approve');
    Route::get('penalty-accruals', [PolicyController::class, 'accruals'])->middleware('permission:penalties.read');
    Route::post('penalty-accruals/run', [PolicyController::class, 'runAccrual'])->middleware('permission:penalties.update');
    Route::post('penalty-accruals/{penaltyAccrual}/crystallize', [PolicyController::class, 'crystallize'])->middleware('permission:penalties.update');
    Route::post('penalty-accruals/{penaltyAccrual}/waive', [PolicyController::class, 'waiveAccrual'])->middleware('permission:penalties.approve');
    Route::get('ukt-groups', [PolicyController::class, 'uktGroups'])->middleware('permission:ukt.read');
    Route::post('ukt-groups', [PolicyController::class, 'storeUktGroup'])->middleware('permission:ukt.create');
    Route::put('ukt-groups/{uktGroup}', [PolicyController::class, 'updateUktGroup'])->middleware('permission:ukt.update');
    Route::get('ukt-assignments', [PolicyController::class, 'uktAssignments'])->middleware('permission:ukt.read');
    Route::post('ukt-assignments', [PolicyController::class, 'assignUkt'])->middleware('permission:ukt.create');
    Route::get('ukt-requests', [PolicyController::class, 'uktRequests'])->middleware('permission:ukt.read');
    Route::post('ukt-requests/{uktChangeRequest}/decide', [PolicyController::class, 'decideUktRequest'])->middleware('permission:ukt.approve');
    Route::get('installment-plans', [PolicyController::class, 'plans'])->middleware('permission:installments.read');
    Route::post('installment-plans', [PolicyController::class, 'storePlan'])->middleware('permission:installments.create');
    Route::put('installment-plans/{installmentPlan}', [PolicyController::class, 'updatePlan'])->middleware('permission:installments.update');
    Route::get('installment-requests', [PolicyController::class, 'installmentRequests'])->middleware('permission:installments.read');
    Route::post('invoices/{invoice}/installments', [PolicyController::class, 'requestInstallment'])->middleware('permission:installments.create');
    Route::post('installment-requests/{installmentRequest}/approve', [PolicyController::class, 'approveInstallment'])->middleware('permission:installments.approve');
    Route::post('installment-requests/{installmentRequest}/reject', [PolicyController::class, 'rejectInstallment'])->middleware('permission:installments.approve');
    Route::post('invoices/{invoice}/installments/cancel', [PolicyController::class, 'cancelSchedule'])->middleware('permission:installments.approve');

    // Cekal keuangan & dispensasi
    Route::get('clearance-rules', [ClearanceController::class, 'rules'])->middleware('permission:finance_clearance.read');
    Route::post('clearance-rules', [ClearanceController::class, 'storeRule'])->middleware('permission:finance_clearance.update');
    Route::put('clearance-rules/{financeClearanceRule}', [ClearanceController::class, 'updateRule'])->middleware('permission:finance_clearance.update');
    Route::get('clearance-rules/{financeClearanceRule}/simulate', [ClearanceController::class, 'simulate'])->middleware('permission:finance_clearance.read');
    Route::post('clearance/evaluate', [ClearanceController::class, 'evaluate'])->middleware('permission:finance_clearance.update');
    Route::get('blocks', [ClearanceController::class, 'blocks'])->middleware('permission:finance_clearance.read');
    Route::get('blocks/{academicBlock}', [ClearanceController::class, 'showBlock'])->middleware('permission:finance_clearance.read');
    Route::post('blocks/{academicBlock}/revoke', [ClearanceController::class, 'revokeBlock'])->middleware('permission:finance_clearance.approve');
    Route::post('blocks/{academicBlock}/dispensations', [ClearanceController::class, 'grant'])->middleware('permission:finance_clearance.create');
    Route::get('dispensations', [ClearanceController::class, 'dispensations'])->middleware('permission:finance_clearance.read');
    Route::post('dispensations/{academicBlockExemption}/decide', [ClearanceController::class, 'decide'])->middleware('permission:finance_clearance.approve');
    Route::post('dispensations/{academicBlockExemption}/revoke', [ClearanceController::class, 'revokeDispensation'])->middleware('permission:finance_clearance.approve');
    Route::get('students/{student}/clearance', [ClearanceController::class, 'studentClearance'])->middleware('permission:finance_clearance.read');

    // Laporan, pengaturan, profil keuangan mahasiswa
    Route::get('reports/{type}', [ReportController::class, 'show'])->middleware('permission:finance_reports.read');
    Route::get('reports/{type}/export', [ReportController::class, 'export'])->middleware('permission:finance_reports.export');
    Route::get('settings', [SettingsController::class, 'show'])->middleware('permission:finance_settings.read');
    Route::put('settings', [SettingsController::class, 'update'])->middleware('permission:finance_settings.update');
    Route::get('payment-channels', [SettingsController::class, 'channels'])->middleware('permission:payment_channels.read');
    Route::post('payment-channels', [SettingsController::class, 'storeChannel'])->middleware('permission:payment_channels.update');
    Route::put('payment-channels/{paymentChannel}', [SettingsController::class, 'updateChannel'])->middleware('permission:payment_channels.update');
    Route::post('payment-channels/{paymentChannel}/status', [SettingsController::class, 'setChannelStatus'])->middleware('permission:payment_channels.update');
    Route::put('payment-channels-quota', [SettingsController::class, 'updateQuotaOverride'])->middleware('permission:payment_channels.update');
    Route::get('students-search', [StudentAccountController::class, 'search'])->middleware('permission:invoices.read,payment_transactions.create');
    Route::get('students/{student}', [StudentAccountController::class, 'show'])->middleware('permission:invoices.read');
    Route::get('students/{student}/statement', [StudentAccountController::class, 'statement'])->middleware('permission:invoices.read');
    Route::post('students/{student}/collection-notes', [StudentAccountController::class, 'storeNote'])->middleware('permission:invoices.update');
});

Route::middleware(['auth:sanctum', 'tenant.access'])->prefix('student/finance')->group(function () {
    Route::middleware('permission:student_finance.read')->group(function (): void {
        Route::get('summary', [StudentFinanceController::class, 'summary']);
        Route::get('invoices', [StudentFinanceController::class, 'invoices']);
        Route::get('invoices/{invoice}', [StudentFinanceController::class, 'invoice']);
        Route::get('invoices/{invoice}/installment-plans', [StudentFinanceController::class, 'installmentPlans']);
        Route::get('channels', [StudentFinanceController::class, 'channels']);
        Route::get('transactions', [StudentFinanceController::class, 'transactions']);
        Route::get('transactions/{transaction}/receipt', [StudentFinanceController::class, 'receipt']);
        Route::get('deposit', [StudentFinanceController::class, 'deposit']);
        Route::get('statement', [StudentFinanceController::class, 'statement']);
        Route::get('ukt', [StudentFinanceController::class, 'ukt']);
        Route::get('awards', [StudentFinanceController::class, 'awards']);
    });

    Route::middleware('permission:student_finance.create')->group(function (): void {
        Route::post('payments/online', [StudentFinanceController::class, 'payOnline'])->middleware('throttle:20,1');
        Route::post('payments/manual', [StudentFinanceController::class, 'prepareManual'])->middleware('throttle:20,1');
        Route::post('transactions/{transaction}/proof', [StudentFinanceController::class, 'uploadProof']);
        Route::post('transactions/{transaction}/cancel', [StudentFinanceController::class, 'cancelTransaction']);
        Route::post('transactions/{transaction}/refresh', [StudentFinanceController::class, 'refreshTransaction'])->middleware('throttle:30,1');
        Route::post('invoices/{invoice}/deposit', [StudentFinanceController::class, 'applyDeposit']);
        Route::post('invoices/{invoice}/voucher', [StudentFinanceController::class, 'redeemVoucher'])->middleware('throttle:10,1');
        Route::post('invoices/{invoice}/installments', [StudentFinanceController::class, 'requestInstallment']);
        Route::post('refunds', [StudentFinanceController::class, 'requestRefund']);
        Route::post('ukt-requests', [StudentFinanceController::class, 'requestUktChange']);
        Route::post('blocks/{block}/dispensations', [StudentFinanceController::class, 'requestDispensation']);
    });
});
