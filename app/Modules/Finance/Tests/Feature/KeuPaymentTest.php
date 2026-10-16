<?php

use Modules\FileManagement\Models\FileUpload;
use Modules\Finance\Enums\DepositEntryType;
use Modules\Finance\Enums\InvoiceStatus;
use Modules\Finance\Enums\ProofStatus;
use Modules\Finance\Enums\TransactionStatus;
use Modules\Finance\Models\Payment;
use Modules\Finance\Models\PaymentProof;
use Modules\Finance\Models\PaymentTransaction;
use Modules\Finance\Models\StudentDepositAccount;
use Modules\Finance\Models\StudentDepositEntry;

/*
| Pembayaran KEU: tunai di loket, transfer manual + verifikasi bukti,
| pembayaran sebagian, kelebihan → deposit, pemakaian deposit, pembalikan.
*/

test('cash payments settle an invoice partially then fully and each gets a receipt number', function () {
    $world = keuWorld();
    $invoice = keuInvoice($world, '1000000');
    $cashier = keuCashier($world);
    $session = keuOpenSession($world, $cashier);

    $first = keuPayCash($world, $cashier, $session, $invoice->id, 400000)->assertApiSuccess(201)
        ->assertJsonPath('data.status', 'success')->json('data');
    expect($first['receipt_number'])->toMatch('/^KWT-\d{6}-\d{6}$/');
    expect($invoice->fresh()->status)->toBe(InvoiceStatus::Partial)
        ->and((string) $invoice->fresh()->paid_amount)->toBe('400000.00');

    keuPayCash($world, $cashier, $session, $invoice->id, 600000)->assertApiSuccess(201);

    expect($invoice->fresh()->status)->toBe(InvoiceStatus::Paid)
        ->and($invoice->fresh()->settled_at)->not->toBeNull()
        ->and(Payment::query()->where('invoice_id', $invoice->id)->where('status', Payment::POSTED)->count())->toBe(2);
});

test('cash cannot be recorded without an open session owned by the cashier', function () {
    $world = keuWorld();
    $invoice = keuInvoice($world);
    $cashier = keuCashier($world);
    $other = keuCashier($world);
    $session = keuOpenSession($world, $cashier);

    keuPayCash($world, $other, $session, $invoice->id, 1000)->assertApiError(409)
        ->assertJsonPath('errors.code', 'CASHIER_SESSION_REQUIRED');

    $this->actingAs($cashier)->withHeaders(portalHeaders($world))
        ->postJson("/api/v1/finance/cashier-sessions/{$session}/close", ['counted_total' => 0])->assertApiSuccess();

    keuPayCash($world, $cashier, $session, $invoice->id, 1000)->assertApiError(409)
        ->assertJsonPath('errors.code', 'CASHIER_SESSION_REQUIRED');
    expect(PaymentTransaction::query()->count())->toBe(0);
});

test('cash payment validation is enforced server side', function () {
    $world = keuWorld();
    $invoice = keuInvoice($world);
    $cashier = keuCashier($world);
    $session = keuOpenSession($world, $cashier);

    keuPayCash($world, $cashier, $session, $invoice->id, 0)->assertApiError(422);
    keuPayCash($world, $cashier, $session, $invoice->id, -5000)->assertApiError(422);

    // Tagihan mahasiswa lain tidak dapat dibayar atas nama mahasiswa ini.
    $foreign = keuInvoice($world, '50000', portalOtherStudent($world));
    keuPayCash($world, $cashier, $session, $foreign->id, 50000)->assertApiError(409);
    expect($foreign->fresh()->status)->toBe(InvoiceStatus::Unpaid);
});

test('overpayment is credited to the student deposit and the deposit can settle another invoice', function () {
    $world = keuWorld();
    $first = keuInvoice($world, '500000');
    $second = keuInvoice($world, '200000');
    $cashier = keuCashier($world);
    $session = keuOpenSession($world, $cashier);

    keuPayCash($world, $cashier, $session, $first->id, 650000)->assertApiSuccess(201);

    expect($first->fresh()->status)->toBe(InvoiceStatus::Paid)
        ->and((string) $first->fresh()->paid_amount)->toBe('500000.00');
    $account = StudentDepositAccount::query()->where('student_id', $world['student']->id)->firstOrFail();
    expect((string) $account->balance)->toBe('150000.00')
        ->and(StudentDepositEntry::query()->where('entry_type', DepositEntryType::Overpayment)->count())->toBe(1);

    // Mahasiswa memakai saldonya sendiri untuk tagihan kedua (sebagian, saldo < tagihan).
    $this->actingAs($world['user'])->withHeaders(portalHeaders($world))
        ->postJson("/api/v1/student/finance/invoices/{$second->id}/deposit")->assertApiSuccess();

    expect($second->fresh()->status)->toBe(InvoiceStatus::Partial)
        ->and((string) $second->fresh()->paid_amount)->toBe('150000.00')
        ->and((string) $account->fresh()->balance)->toBe('0.00');

    // Saldo sudah habis → ditolak, tidak ada mutasi negatif.
    $this->postJson("/api/v1/student/finance/invoices/{$second->id}/deposit")->assertApiError(409)
        ->assertJsonPath('errors.code', 'INSUFFICIENT_DEPOSIT');
    expect((string) $account->fresh()->balance)->toBe('0.00');
});

test('manual transfer proof is verified by finance staff and cannot be decided twice', function () {
    $world = keuWorld();
    $invoice = keuInvoice($world, '750000');
    $headers = portalHeaders($world);

    $tx = $this->actingAs($world['user'])->withHeaders($headers)
        ->postJson('/api/v1/student/finance/payments/manual', ['target_type' => 'invoice', 'target_id' => $invoice->id])
        ->assertApiSuccess(201)->assertJsonPath('data.status', 'pending')->json('data');

    $file = portalAsTenant($world['university'], fn () => FileUpload::factory()->create([
        'university_id' => $world['university']->id, 'uploaded_by' => $world['user']->id, 'extension' => 'jpg',
    ]));

    $this->postJson("/api/v1/student/finance/transactions/{$tx['id']}/proof", [
        'file_upload_id' => $file->id, 'sender_bank' => 'BNI', 'sender_name' => $world['student']->name,
        'transfer_date' => now()->toDateString(), 'transferred_amount' => $tx['amount'],
    ])->assertApiSuccess()->assertJsonPath('data.status', 'awaiting_verification');

    // Mahasiswa tidak memiliki izin verifikasi.
    $proof = PaymentProof::query()->firstOrFail();
    $this->postJson("/api/v1/finance/payment-proofs/{$proof->id}/approve")->assertApiError(403);

    $verifier = keuStaff($world, ['payment_transactions.read', 'payment_transactions.approve']);
    $this->actingAs($verifier)->withHeaders($headers)
        ->postJson("/api/v1/finance/payment-proofs/{$proof->id}/approve", ['note' => 'Cocok dengan mutasi'])
        ->assertApiSuccess()->assertJsonPath('data.status', 'success');

    expect($invoice->fresh()->status)->toBe(InvoiceStatus::Paid)
        ->and($proof->fresh()->status)->toBe(ProofStatus::Approved);

    $this->postJson("/api/v1/finance/payment-proofs/{$proof->id}/approve")->assertApiError(409)
        ->assertJsonPath('errors.code', 'ALREADY_DECIDED');
    expect(Payment::query()->where('invoice_id', $invoice->id)->count())->toBe(1);
});

test('rejected proof fails the transaction, notifies the reason, and can be re-uploaded', function () {
    $world = keuWorld();
    $invoice = keuInvoice($world, '300000');
    $headers = portalHeaders($world);
    $tx = $this->actingAs($world['user'])->withHeaders($headers)
        ->postJson('/api/v1/student/finance/payments/manual', ['target_type' => 'invoice', 'target_id' => $invoice->id])->json('data');
    $files = portalAsTenant($world['university'], fn () => FileUpload::factory()->count(2)->create([
        'university_id' => $world['university']->id, 'uploaded_by' => $world['user']->id, 'extension' => 'png',
    ]));
    $payload = ['sender_bank' => 'BCA', 'sender_name' => 'Wali', 'transfer_date' => now()->toDateString(), 'transferred_amount' => $tx['amount']];

    $this->postJson("/api/v1/student/finance/transactions/{$tx['id']}/proof", [...$payload, 'file_upload_id' => $files[0]->id])->assertApiSuccess();

    $verifier = keuStaff($world, ['payment_transactions.read', 'payment_transactions.approve']);
    $proof = PaymentProof::query()->firstOrFail();
    $this->actingAs($verifier)->withHeaders($headers)
        ->postJson("/api/v1/finance/payment-proofs/{$proof->id}/reject", ['reason_code' => 'BOGUS'])->assertApiError(422);
    $this->postJson("/api/v1/finance/payment-proofs/{$proof->id}/reject", ['reason_code' => 'UNREADABLE', 'note' => 'Foto buram'])->assertApiSuccess();

    expect(PaymentTransaction::query()->find($tx['id'])->status)->toBe(TransactionStatus::Failed)
        ->and($invoice->fresh()->status)->toBe(InvoiceStatus::Unpaid);

    $this->actingAs($world['user'])->withHeaders($headers)
        ->postJson("/api/v1/student/finance/transactions/{$tx['id']}/proof", [...$payload, 'file_upload_id' => $files[1]->id])
        ->assertApiSuccess()->assertJsonPath('data.status', 'awaiting_verification');
});

test('a proof uploaded with a file belonging to someone else is rejected', function () {
    $world = keuWorld();
    $invoice = keuInvoice($world);
    $tx = $this->actingAs($world['user'])->withHeaders(portalHeaders($world))
        ->postJson('/api/v1/student/finance/payments/manual', ['target_type' => 'invoice', 'target_id' => $invoice->id])->json('data');
    $foreignFile = portalAsTenant($world['university'], fn () => FileUpload::factory()->create(['university_id' => $world['university']->id, 'extension' => 'jpg']));

    $this->postJson("/api/v1/student/finance/transactions/{$tx['id']}/proof", [
        'file_upload_id' => $foreignFile->id, 'sender_bank' => 'BNI', 'sender_name' => 'X', 'transfer_date' => now()->toDateString(), 'transferred_amount' => 1000,
    ])->assertApiError(422);
});

test('reversal enforces separation of duties and restores the invoice and deposit', function () {
    $world = keuWorld();
    $invoice = keuInvoice($world, '100000');
    $cashier = keuStaff($world, ['cashier_sessions.create', 'payment_transactions.create', 'payment_transactions.approve']);
    $session = keuOpenSession($world, $cashier);
    $tx = keuPayCash($world, $cashier, $session, $invoice->id, 120000)->assertApiSuccess(201)->json('data');
    $reason = ['reason' => 'Uang palsu terdeteksi saat setoran bank'];

    $this->actingAs($cashier)->withHeaders(portalHeaders($world))
        ->postJson("/api/v1/finance/transactions/{$tx['id']}/reverse", $reason)->assertApiError(409)
        ->assertJsonPath('errors.code', 'SELF_APPROVAL_FORBIDDEN');

    $supervisor = keuStaff($world, ['payment_transactions.approve']);
    $this->actingAs($supervisor)->withHeaders(portalHeaders($world))
        ->postJson("/api/v1/finance/transactions/{$tx['id']}/reverse", ['reason' => 'pendek'])->assertApiError(422);
    $this->postJson("/api/v1/finance/transactions/{$tx['id']}/reverse", $reason)->assertApiSuccess()
        ->assertJsonPath('data.status', 'reversed');

    expect($invoice->fresh()->status)->toBe(InvoiceStatus::Unpaid)
        ->and((string) $invoice->fresh()->paid_amount)->toBe('0.00')
        ->and((string) StudentDepositAccount::query()->where('student_id', $world['student']->id)->value('balance'))->toBe('0.00')
        ->and(Payment::query()->where('invoice_id', $invoice->id)->where('status', Payment::REVERSED)->count())->toBe(1);

    $this->postJson("/api/v1/finance/transactions/{$tx['id']}/reverse", $reason)->assertApiError(409);
});

test('cashier session difference is recorded on close and the deposit is verified by someone else', function () {
    $world = keuWorld();
    $invoice = keuInvoice($world, '250000');
    $cashier = keuCashier($world);
    $session = keuOpenSession($world, $cashier);
    keuPayCash($world, $cashier, $session, $invoice->id, 250000)->assertApiSuccess(201);

    $this->actingAs($cashier)->withHeaders(portalHeaders($world))
        ->postJson("/api/v1/finance/cashier-sessions/{$session}/close", ['counted_total' => 240000, 'note' => 'Kurang 10rb'])
        ->assertApiSuccess()->assertJsonPath('data.difference', '-10000.00');
    $this->postJson("/api/v1/finance/cashier-sessions/{$session}/deposit", ['deposit_reference' => 'SETOR-001'])->assertApiSuccess();

    $cashierAsVerifier = keuStaff($world, ['cashier_sessions.approve']);
    portalGrant($world, $cashier, ['cashier_sessions.approve']);
    $this->actingAs($cashier)->withHeaders(portalHeaders($world))
        ->postJson("/api/v1/finance/cashier-sessions/{$session}/verify")->assertApiError(409)
        ->assertJsonPath('errors.code', 'SELF_APPROVAL_FORBIDDEN');
    $this->actingAs($cashierAsVerifier)->withHeaders(portalHeaders($world))
        ->postJson("/api/v1/finance/cashier-sessions/{$session}/verify")->assertApiSuccess()
        ->assertJsonPath('data.status', 'verified');
});

test('the transaction list date range filters on the effective payment date', function () {
    $world = keuWorld();
    $make = fn (string $number, ?string $paidAt, string $createdAt) => portalAsTenant($world['university'], fn () => PaymentTransaction::query()->forceCreate([
        'university_id' => $world['university']->id, 'transaction_number' => $number, 'student_id' => $world['student']->id, 'provider' => 'manual',
        'method' => 'manual_transfer', 'target_type' => 'deposit', 'amount' => 1000, 'status' => $paidAt ? 'success' : 'pending',
        'paid_at' => $paidAt, 'reconciliation_status' => 'unreconciled', 'created_at' => $createdAt, 'updated_at' => $createdAt,
    ]));
    // Bukti diverifikasi hari ini tetapi transfer kemarin → masuk rentang "kemarin".
    $make('TRX-LATE-VERIFIED', now()->subDay()->setTime(12, 0)->toDateTimeString(), now()->toDateTimeString());
    $make('TRX-PENDING-TODAY', null, now()->toDateTimeString());
    $yesterday = now()->subDay()->toDateString();

    $this->actingAs(keuStaff($world, ['payment_transactions.read']))->withHeaders(portalHeaders($world))
        ->getJson("/api/v1/finance/transactions?date_from={$yesterday}&date_to={$yesterday}")
        ->assertApiSuccess()->assertJsonCount(1, 'data')->assertJsonPath('data.0.transaction_number', 'TRX-LATE-VERIFIED');
});

test('a new manual transfer instruction replaces the previous one that has no proof yet', function () {
    $world = keuWorld();
    $invoice = keuInvoice($world, '400000');
    $headers = portalHeaders($world);

    $first = $this->actingAs($world['user'])->withHeaders($headers)
        ->postJson('/api/v1/student/finance/payments/manual', ['target_type' => 'invoice', 'target_id' => $invoice->id])->assertApiSuccess(201)->json('data.id');
    $second = $this->postJson('/api/v1/student/finance/payments/manual', ['target_type' => 'invoice', 'target_id' => $invoice->id])->assertApiSuccess(201)->json('data.id');

    expect(PaymentTransaction::query()->find($first)->status)->toBe(TransactionStatus::Cancelled)
        ->and(PaymentTransaction::query()->find($second)->status)->toBe(TransactionStatus::Pending);
});
