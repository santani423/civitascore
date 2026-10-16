<?php

use Carbon\CarbonImmutable;
use Modules\Finance\Enums\BillGroup;
use Modules\Finance\Enums\InstallmentStatus;
use Modules\Finance\Enums\InvoiceStatus;
use Modules\Finance\Enums\PenaltyAccrualStatus;
use Modules\Finance\Enums\RefundStatus;
use Modules\Finance\Models\InstallmentPlan;
use Modules\Finance\Models\Invoice;
use Modules\Finance\Models\InvoiceInstallment;
use Modules\Finance\Models\PenaltyAccrual;
use Modules\Finance\Models\PenaltyRule;
use Modules\Finance\Models\StudentDepositAccount;
use Modules\Finance\Models\Voucher;

/*
| Cicilan, voucher, denda keterlambatan, dan pengembalian dana (deposit).
*/

function keuPlan(array $world, bool $autoApprove = true): InstallmentPlan
{
    return portalAsTenant($world['university'], fn () => InstallmentPlan::query()->create([
        'code' => 'CICIL3', 'name' => 'Cicilan 3x', 'bill_groups' => ['registration'],
        'terms' => [['percent' => 40, 'due_offset_days' => 7], ['percent' => 30, 'due_offset_days' => 37], ['percent' => 30, 'due_offset_days' => 67]],
        'auto_approve' => $autoApprove, 'require_no_arrears' => true, 'is_active' => true,
    ]));
}

function keuVoucher(array $world, array $attributes = []): Voucher
{
    return portalAsTenant($world['university'], fn () => Voucher::query()->create([
        'code' => 'HEMAT10', 'name' => 'Potongan 10%', 'discount_type' => 'percent', 'discount_value' => 10,
        'quota_total' => 10, 'quota_used' => 0, 'per_student_limit' => 1,
        'valid_from' => now()->subDay(), 'valid_until' => now()->addDays(10), 'status' => 'active', ...$attributes,
    ]));
}

function keuPenaltyRule(array $world, array $attributes = []): PenaltyRule
{
    return portalAsTenant($world['university'], fn () => PenaltyRule::query()->create([
        'code' => 'DENDA', 'name' => 'Denda keterlambatan', 'bill_groups' => ['registration'], 'grace_days' => 0,
        'calc_type' => 'flat', 'period_unit' => 'once', 'flat_amount' => 50000,
        'effective_from' => now()->subMonths(2)->toDateString(), 'status' => 'active', ...$attributes,
    ]));
}

/** Saldo deposit lewat kelebihan bayar tunai. */
function keuFundDeposit(array $world, int $amount): void
{
    $invoice = keuInvoice($world, '10000');
    $cashier = keuCashier($world);
    keuPayCash($world, $cashier, keuOpenSession($world, $cashier), $invoice->id, 10000 + $amount)->assertApiSuccess(201);
}

test('a standard installment plan is auto-approved with terms summing to the outstanding amount', function () {
    $world = keuWorld();
    $invoice = keuInvoice($world, '1000000');
    keuPlan($world);
    $headers = portalHeaders($world);

    $planId = $this->actingAs($world['user'])->withHeaders($headers)->getJson("/api/v1/student/finance/invoices/{$invoice->id}/installment-plans")
        ->assertApiSuccess()->assertJsonCount(1, 'data')->json('data.0.id');
    $this->postJson("/api/v1/student/finance/invoices/{$invoice->id}/installments", ['installment_plan_id' => $planId])
        ->assertApiSuccess(201)->assertJsonPath('data.status', 'approved');

    $terms = InvoiceInstallment::query()->where('invoice_id', $invoice->id)->whereNull('superseded_at')->orderBy('seq')->get();
    expect($terms)->toHaveCount(3)
        ->and($terms->map(fn ($t) => (string) $t->amount)->all())->toBe(['400000.00', '300000.00', '300000.00']);

    // Sudah punya jadwal aktif → pengajuan baru ditolak; cicilan khusus tanpa alasan ditolak.
    $this->postJson("/api/v1/student/finance/invoices/{$invoice->id}/installments", ['installment_plan_id' => $planId])->assertApiError(409);
    $this->postJson("/api/v1/student/finance/invoices/{$invoice->id}/installments", ['terms' => [['percent' => 50], ['percent' => 50]], 'kind' => 'reschedule'])->assertApiError(409);

    // Bayar termin 1 di loket → termin 1 lunas, tagihan sebagian.
    $cashier = keuCashier($world);
    keuPayCash($world, $cashier, keuOpenSession($world, $cashier), $terms[0]->id, 400000, 'installment')->assertApiSuccess(201);

    expect($terms[0]->fresh()->status)->toBe(InstallmentStatus::Paid)
        ->and($terms[1]->fresh()->status)->toBe(InstallmentStatus::Scheduled)
        ->and($invoice->fresh()->status)->toBe(InvoiceStatus::Partial);
});

test('custom installment terms need approval by someone other than the requester and must total 100 percent', function () {
    $world = keuWorld();
    $invoice = keuInvoice($world, '900000');
    $headers = portalHeaders($world);

    $this->actingAs($world['user'])->withHeaders($headers)
        ->postJson("/api/v1/student/finance/invoices/{$invoice->id}/installments", ['terms' => [['percent' => 60], ['percent' => 30]], 'reason' => 'Orang tua baru gajian akhir bulan'])
        ->assertApiError(409);
    $this->postJson("/api/v1/student/finance/invoices/{$invoice->id}/installments", ['terms' => [['percent' => 50], ['percent' => 50]]])->assertApiError(409);

    $requestId = $this->postJson("/api/v1/student/finance/invoices/{$invoice->id}/installments", ['terms' => [['percent' => 50], ['percent' => 50]], 'reason' => 'Orang tua baru gajian akhir bulan'])
        ->assertApiSuccess(201)->assertJsonPath('data.status', 'submitted')->json('data.id');
    expect(InvoiceInstallment::query()->count())->toBe(0);

    $approver = keuStaff($world, ['installments.read', 'installments.approve']);
    $this->actingAs($approver)->withHeaders($headers)->postJson("/api/v1/finance/installment-requests/{$requestId}/approve")->assertApiSuccess();
    expect(InvoiceInstallment::query()->where('invoice_id', $invoice->id)->sum('amount'))->toEqual(900000);
});

test('a voucher discounts an unpaid invoice once and respects quota and pending transactions', function () {
    $world = keuWorld();
    $invoice = keuInvoice($world, '1000000');
    keuVoucher($world, ['quota_total' => 1]);
    $headers = portalHeaders($world);

    $this->actingAs($world['user'])->withHeaders($headers)
        ->postJson("/api/v1/student/finance/invoices/{$invoice->id}/voucher", ['code' => 'TIDAKADA'])->assertApiError(409)
        ->assertJsonPath('errors.code', 'VOUCHER_INVALID');
    $this->postJson("/api/v1/student/finance/invoices/{$invoice->id}/voucher", ['code' => 'hemat10'])
        ->assertApiSuccess()->assertJsonPath('data.discount_amount', '100000.00');

    expect((string) $invoice->fresh()->amount)->toBe('900000.00')
        ->and((string) $invoice->fresh()->discount_amount)->toBe('100000.00')
        ->and(Voucher::query()->value('quota_used'))->toBe(1);

    // Kuota habis untuk mahasiswa lain.
    $other = keuOtherStudentUser($world);
    $otherInvoice = keuInvoice($world, '1000000', $other['student']);
    $this->actingAs($other['user'])->withHeaders($headers)
        ->postJson("/api/v1/student/finance/invoices/{$otherInvoice->id}/voucher", ['code' => 'HEMAT10'])->assertApiError(409)
        ->assertJsonPath('errors.code', 'VOUCHER_EXHAUSTED');
});

test('a voucher cannot be redeemed while a payment for the invoice is in progress', function () {
    $world = keuWorld();
    $invoice = keuInvoice($world, '1000000');
    keuVoucher($world);
    $headers = portalHeaders($world);

    $this->actingAs($world['user'])->withHeaders($headers)
        ->postJson('/api/v1/student/finance/payments/manual', ['target_type' => 'invoice', 'target_id' => $invoice->id])->assertApiSuccess(201);
    $this->postJson("/api/v1/student/finance/invoices/{$invoice->id}/voucher", ['code' => 'HEMAT10'])->assertApiError(409)
        ->assertJsonPath('errors.code', 'PENDING_TRANSACTION_EXISTS');
    expect((string) $invoice->fresh()->amount)->toBe('1000000.00');
});

test('penalty rules need a second approver and late payment crystallizes the penalty as a separate invoice', function () {
    $world = keuWorld();
    $maker = keuStaff($world, ['penalties.read', 'penalties.create', 'penalties.update', 'penalties.approve']);
    $checker = keuStaff($world, ['penalties.read', 'penalties.approve', 'penalties.update']);
    $headers = portalHeaders($world);

    $rule = $this->actingAs($maker)->withHeaders($headers)->postJson('/api/v1/finance/penalty-rules', [
        'code' => 'denda-bpp', 'name' => 'Denda BPP', 'bill_groups' => ['registration'], 'grace_days' => 3,
        'calc_type' => 'percent', 'period_unit' => 'day', 'rate_percent' => 0.1, 'cap_percent' => 5,
        'effective_from' => now()->subYear()->toDateString(),
    ])->assertApiSuccess(201)->json('data.id');
    $this->postJson("/api/v1/finance/penalty-rules/{$rule}/submit")->assertApiSuccess();
    $this->postJson("/api/v1/finance/penalty-rules/{$rule}/approve")->assertApiError(409)->assertJsonPath('errors.code', 'SELF_APPROVAL_FORBIDDEN');
    $this->actingAs($checker)->withHeaders($headers)->postJson("/api/v1/finance/penalty-rules/{$rule}/approve")->assertApiSuccess()->assertJsonPath('data.status', 'active');

    // Jatuh tempo 13 hari lalu, tenggang 3 hari → 10 hari terlambat × 0,1% × 1.000.000 = 10.000.
    $invoice = keuInvoice($world, '1000000', dueDate: CarbonImmutable::today()->subDays(13));
    $this->postJson('/api/v1/finance/penalty-accruals/run')->assertApiSuccess()->assertJsonPath('data.updated', 1);
    $accrual = PenaltyAccrual::query()->sole();
    expect((string) $accrual->accrued_amount)->toBe('10000.00')
        ->and($accrual->status)->toBe(PenaltyAccrualStatus::Accruing);

    // Pokok dilunasi hari ini → denda dikristalisasi menjadi tagihan denda.
    $cashier = keuCashier($world);
    keuPayCash($world, $cashier, keuOpenSession($world, $cashier), $invoice->id, 1000000)->assertApiSuccess(201);

    $penaltyInvoice = Invoice::query()->where('bill_group', BillGroup::Penalty)->sole();
    expect($accrual->fresh()->status)->toBe(PenaltyAccrualStatus::Crystallized)
        ->and($penaltyInvoice->parent_invoice_id)->toBe($invoice->id)
        ->and((string) $penaltyInvoice->amount)->toBe('10000.00')
        ->and($invoice->fresh()->status)->toBe(InvoiceStatus::Paid);

    // Tidak ada denda atas denda dan job berikutnya tidak menggandakan.
    $this->actingAs($checker)->withHeaders($headers)->postJson('/api/v1/finance/penalty-accruals/run')->assertApiSuccess();
    expect(PenaltyAccrual::query()->count())->toBe(1)
        ->and(Invoice::query()->where('bill_group', BillGroup::Penalty)->count())->toBe(1);
});

test('penalties are capped and an invoice paid on time incurs none', function () {
    $world = keuWorld();
    keuPenaltyRule($world, ['calc_type' => 'percent', 'period_unit' => 'day', 'rate_percent' => 1, 'cap_amount' => 25000, 'flat_amount' => null]);
    $late = keuInvoice($world, '1000000', dueDate: CarbonImmutable::today()->subDays(40));
    keuInvoice($world, '1000000');
    $staff = keuStaff($world, ['penalties.update']);

    $this->actingAs($staff)->withHeaders(portalHeaders($world))->postJson('/api/v1/finance/penalty-accruals/run')->assertApiSuccess();

    expect(PenaltyAccrual::query()->count())->toBe(1)
        ->and(PenaltyAccrual::query()->value('invoice_id'))->toBe($late->id)
        ->and((string) PenaltyAccrual::query()->value('accrued_amount'))->toBe('25000.00');
});

test('refunds hold deposit funds, need a different approver, and release on rejection', function () {
    $world = keuWorld();
    keuFundDeposit($world, 300000);
    $headers = portalHeaders($world);
    $account = fn () => StudentDepositAccount::query()->where('student_id', $world['student']->id)->firstOrFail();
    $beneficiary = ['beneficiary_bank' => 'BNI', 'beneficiary_account' => '1234567890', 'beneficiary_name' => 'Budi', 'beneficiary_relation' => 'self'];

    $refund = $this->actingAs($world['user'])->withHeaders($headers)->postJson('/api/v1/student/finance/refunds', [
        ...$beneficiary, 'amount' => 200000, 'reason_code' => 'OVERPAYMENT', 'reason_note' => 'Kelebihan bayar semester ini',
    ])->assertApiSuccess(201)->assertJsonPath('data.status', 'pending_approval')->json('data');
    expect($refund['beneficiary_account'])->not->toBe('1234567890')
        ->and((string) $account()->held_amount)->toBe('200000.00');

    // Saldo tersedia tinggal 100.000.
    $this->postJson('/api/v1/student/finance/refunds', [...$beneficiary, 'amount' => 150000, 'reason_code' => 'OVERPAYMENT', 'reason_note' => 'Pengajuan kedua melebihi saldo'])
        ->assertApiError(409)->assertJsonPath('errors.code', 'INSUFFICIENT_DEPOSIT');

    $approver = keuStaff($world, ['refunds.read', 'refunds.approve']);
    $finisher = keuStaff($world, ['refunds.finalize']);
    $this->actingAs($approver)->withHeaders($headers)->postJson("/api/v1/finance/refunds/{$refund['id']}/approve")->assertApiSuccess()->assertJsonPath('data.status', 'approved');
    $this->actingAs($finisher)->withHeaders($headers)->postJson("/api/v1/finance/refunds/{$refund['id']}/complete", [])->assertApiError(422);
    $this->postJson("/api/v1/finance/refunds/{$refund['id']}/complete", ['execution_reference' => 'TRF-OUT-0001'])->assertApiSuccess()->assertJsonPath('data.status', 'completed');

    expect((string) $account()->balance)->toBe('100000.00')->and((string) $account()->held_amount)->toBe('0.00');
});

test('staff cannot approve a refund they requested and a rejected refund releases the hold', function () {
    $world = keuWorld();
    keuFundDeposit($world, 100000);
    $staff = keuStaff($world, ['refunds.read', 'refunds.create', 'refunds.approve']);
    $headers = portalHeaders($world);
    $payload = ['student_id' => $world['student']->id, 'amount' => 100000, 'reason_code' => 'WITHDRAWAL', 'reason_note' => 'Mengundurkan diri dari kampus',
        'beneficiary_bank' => 'BRI', 'beneficiary_account' => '9876543210', 'beneficiary_name' => 'Ahli waris', 'beneficiary_relation' => 'other'];

    $this->actingAs($staff)->withHeaders($headers)->postJson('/api/v1/finance/refunds', $payload)->assertApiError(409);
    $refund = $this->postJson('/api/v1/finance/refunds', [...$payload, 'power_of_attorney' => true])->assertApiSuccess(201)->json('data.id');
    $this->postJson("/api/v1/finance/refunds/{$refund}/approve")->assertApiError(409)->assertJsonPath('errors.code', 'SELF_APPROVAL_FORBIDDEN');

    $this->actingAs(keuStaff($world, ['refunds.approve']))->withHeaders($headers)
        ->postJson("/api/v1/finance/refunds/{$refund}/reject", ['note' => 'Dokumen kuasa belum dilegalisir'])->assertApiSuccess()
        ->assertJsonPath('data.status', RefundStatus::Rejected->value);

    $account = StudentDepositAccount::query()->where('student_id', $world['student']->id)->firstOrFail();
    expect((string) $account->balance)->toBe('100000.00')->and((string) $account->held_amount)->toBe('0.00');
});
