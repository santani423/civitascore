<?php

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Modules\Academic\Enums\AcademicBlockStatus;
use Modules\Academic\Models\AcademicBlock;
use Modules\Academic\Models\AcademicBlockHistory;
use Modules\Academic\Models\Exam;
use Modules\Academic\Models\ExamAttempt;
use Modules\Academic\Services\AcademicBlockService;
use Modules\Finance\Enums\InvoiceStatus;
use Modules\Finance\Enums\ReconciliationStatus;
use Modules\Finance\Enums\TransactionStatus;
use Modules\Finance\Models\FinanceClearanceRule;
use Modules\Finance\Models\Payment;
use Modules\Finance\Models\PaymentCallback;
use Modules\Finance\Models\PaymentChannel;
use Modules\Finance\Models\PaymentTransaction;
use Modules\Finance\Payments\ChargeResult;
use Modules\Finance\Payments\GatewayRegistry;
use Modules\Finance\Payments\GatewayStatus;
use Modules\Finance\Payments\PaymentGateway;
use Modules\Finance\Payments\WebhookEvent;
use Modules\Tenancy\Models\SubscriptionPlan;
use Modules\Tenancy\Models\UniversitySubscription;

/*
| Integrasi KEU: pembayaran online lewat adapter palsu (callback, dedupe,
| idempotensi), kuota kanal paket, rekonsiliasi, cekal keuangan ↔ KRS
| SIAKAD, isolasi data mahasiswa, dan konsistensi laporan/dashboard.
*/

/** Adapter penyedia palsu: status cek-ulang dapat diatur dari test. */
function keuFakeGateway(): PaymentGateway
{
    $gateway = new class implements PaymentGateway
    {
        public string $status = GatewayStatus::PENDING;

        public ?string $amount = null;

        public function name(): string
        {
            return 'fakepay';
        }

        public function supportedMethods(): array
        {
            return ['va'];
        }

        public function createCharge(PaymentTransaction $transaction, PaymentChannel $channel): ChargeResult
        {
            return new ChargeResult('FP-'.$transaction->transaction_number, vaNumber: '8800'.random_int(100000, 999999));
        }

        public function getStatus(string $providerReference): GatewayStatus
        {
            return new GatewayStatus($this->status, $this->amount, now()->toIso8601String());
        }

        public function cancel(string $providerReference): bool
        {
            return true;
        }

        public function verifyWebhook(Request $request): ?WebhookEvent
        {
            return $request->header('X-Signature') === 'valid'
                ? new WebhookEvent((string) $request->input('event_id'), (string) $request->input('reference'), $request->all())
                : null;
        }
    };

    app(GatewayRegistry::class)->register($gateway);

    return $gateway;
}

function keuChannel(array $world, array $attributes = []): PaymentChannel
{
    return portalAsTenant($world['university'], fn () => PaymentChannel::query()->create([
        'code' => 'VA-'.strtoupper(fake()->unique()->lexify('???')), 'name' => 'Virtual Account', 'category' => 'bank', 'provider' => 'fakepay', 'method' => 'va',
        'fee_amount' => 0, 'fee_bearer' => 'campus', 'expiry_minutes' => 1440, 'status' => 'active', 'counts_toward_quota' => true,
        'environment' => 'production', 'sort_order' => 1, ...$attributes,
    ]));
}

test('online payment is idempotent and only a signed, rechecked callback settles it exactly once', function () {
    $world = keuWorld();
    $invoice = keuInvoice($world, '1500000');
    $gateway = keuFakeGateway();
    $channel = keuChannel($world);
    $headers = portalHeaders($world);
    $payload = ['payment_channel_id' => $channel->id, 'target_type' => 'invoice', 'target_id' => $invoice->id];

    $this->actingAs($world['user'])->withHeaders($headers)->getJson('/api/v1/student/finance/channels')
        ->assertApiSuccess()->assertJsonPath('data.online.0.id', $channel->id);
    $this->postJson('/api/v1/student/finance/payments/online', $payload)->assertApiError(409)->assertJsonPath('errors.code', 'IDEMPOTENCY_REQUIRED');

    $tx = $this->withHeaders(['Idempotency-Key' => 'key-1'])->postJson('/api/v1/student/finance/payments/online', $payload)
        ->assertApiSuccess(201)->assertJsonPath('data.status', 'pending')->json('data');
    expect($tx['va_number'])->not->toBeNull();
    $this->withHeaders(['Idempotency-Key' => 'key-1'])->postJson('/api/v1/student/finance/payments/online', $payload)->assertJsonPath('data.id', $tx['id']);
    $this->withHeaders(['Idempotency-Key' => 'key-1'])->postJson('/api/v1/student/finance/payments/online', [...$payload, 'target_id' => keuInvoice($world)->id])
        ->assertApiError(409)->assertJsonPath('errors.code', 'IDEMPOTENCY_MISMATCH');

    $reference = PaymentTransaction::query()->find($tx['id'])->provider_reference;
    $webhook = ['event_id' => 'evt-1', 'reference' => $reference];

    // Tanda tangan salah → ditolak & dicatat, transaksi tidak berubah.
    $this->withHeaders(['X-Signature' => 'forged'])->postJson('/api/v1/finance/webhooks/payments/fakepay', $webhook)->assertStatus(401);
    expect(PaymentCallback::query()->where('signature_valid', false)->count())->toBe(1)
        ->and(PaymentTransaction::query()->find($tx['id'])->status)->toBe(TransactionStatus::Pending);

    // Callback sah tetapi penyedia masih pending (cek ulang) → belum lunas.
    $this->withHeaders(['X-Signature' => 'valid'])->postJson('/api/v1/finance/webhooks/payments/fakepay', [...$webhook, 'event_id' => 'evt-0'])->assertOk();
    expect($invoice->fresh()->status)->toBe(InvoiceStatus::Unpaid);

    $gateway->status = GatewayStatus::SUCCESS;
    $this->withHeaders(['X-Signature' => 'valid'])->postJson('/api/v1/finance/webhooks/payments/fakepay', $webhook)->assertOk();
    $this->withHeaders(['X-Signature' => 'valid'])->postJson('/api/v1/finance/webhooks/payments/fakepay', $webhook)->assertOk()->assertJsonPath('message', 'Sudah diproses.');

    expect(PaymentTransaction::query()->find($tx['id'])->status)->toBe(TransactionStatus::Success)
        ->and($invoice->fresh()->status)->toBe(InvoiceStatus::Paid)
        ->and(Payment::query()->where('invoice_id', $invoice->id)->count())->toBe(1);
});

test('a provider amount mismatch is flagged for reconciliation instead of being posted', function () {
    $world = keuWorld();
    $invoice = keuInvoice($world, '500000');
    $gateway = keuFakeGateway();
    $channel = keuChannel($world);

    $tx = $this->actingAs($world['user'])->withHeaders([...portalHeaders($world), 'Idempotency-Key' => 'k-mismatch'])
        ->postJson('/api/v1/student/finance/payments/online', ['payment_channel_id' => $channel->id, 'target_type' => 'invoice', 'target_id' => $invoice->id])->json('data.id');
    $gateway->status = GatewayStatus::SUCCESS;
    $gateway->amount = '499000.00';

    $this->withHeaders(['X-Signature' => 'valid'])->postJson('/api/v1/finance/webhooks/payments/fakepay', ['event_id' => 'evt-x', 'reference' => PaymentTransaction::query()->find($tx)->provider_reference])->assertOk();

    $fresh = PaymentTransaction::query()->find($tx);
    expect($fresh->status)->toBe(TransactionStatus::Pending)
        ->and($fresh->reconciliation_status)->toBe(ReconciliationStatus::Exception)
        ->and($invoice->fresh()->status)->toBe(InvoiceStatus::Unpaid);
});

test('online channels without a configured provider are never offered or accepted', function () {
    $world = keuWorld();
    $invoice = keuInvoice($world);
    $channel = keuChannel($world, ['provider' => 'midtrans']);

    $this->actingAs($world['user'])->withHeaders(portalHeaders($world))->getJson('/api/v1/student/finance/channels')
        ->assertApiSuccess()->assertJsonCount(0, 'data.online')->assertJsonPath('data.manual_transfer.enabled', true);
    $this->withHeaders(['Idempotency-Key' => 'k-2'])->postJson('/api/v1/student/finance/payments/online', ['payment_channel_id' => $channel->id, 'target_type' => 'invoice', 'target_id' => $invoice->id])
        ->assertApiError(409)->assertJsonPath('errors.code', 'CHANNEL_NOT_CONNECTED');
    expect(PaymentTransaction::query()->count())->toBe(0);
});

test('activating production online channels is limited by the package quota while offline and sandbox are not counted', function () {
    $world = keuWorld();
    foreach (range(1, 3) as $i) {
        keuChannel($world, ['code' => "BANK-{$i}"]);
    }
    $fourth = keuChannel($world, ['code' => 'BANK-4', 'status' => 'inactive']);
    $sandbox = keuChannel($world, ['code' => 'SANDBOX', 'status' => 'inactive', 'environment' => 'sandbox']);
    $offline = keuChannel($world, ['code' => 'LOKET', 'status' => 'inactive', 'category' => 'offline', 'provider' => 'cash', 'method' => 'cash', 'counts_toward_quota' => false]);
    $admin = keuStaff($world, ['payment_channels.read', 'payment_channels.update']);
    $headers = portalHeaders($world);

    $this->actingAs($admin)->withHeaders($headers)->getJson('/api/v1/finance/payment-channels')->assertApiSuccess();
    $this->postJson("/api/v1/finance/payment-channels/{$fourth->id}/status", ['status' => 'active'])->assertApiError(409)
        ->assertJsonPath('errors.code', 'CHANNEL_QUOTA_EXCEEDED');
    $this->postJson("/api/v1/finance/payment-channels/{$sandbox->id}/status", ['status' => 'active'])->assertApiSuccess();
    $this->postJson("/api/v1/finance/payment-channels/{$offline->id}/status", ['status' => 'active'])->assertApiSuccess()
        ->assertJsonPath('data.quota.used', 3)->assertJsonPath('data.quota.limit', 3);

    // Tenant admin tidak dapat menaikkan kuota sendiri (hanya Super Admin platform).
    $this->putJson('/api/v1/finance/payment-channels-quota', ['limit' => 10])->assertApiError(403);

    // Paket Professional → 4 kanal (config/keu.php); kanal ke-4 kini boleh aktif.
    $plan = SubscriptionPlan::factory()->create(['code' => 'professional']);
    UniversitySubscription::factory()->create(['university_id' => $world['university']->id, 'subscription_plan_id' => $plan->id]);
    $this->postJson("/api/v1/finance/payment-channels/{$fourth->id}/status", ['status' => 'active'])->assertApiSuccess()
        ->assertJsonPath('data.quota.limit', 4)->assertJsonPath('data.quota.used', 4);
});

test('reconciliation matches statement rows, keeps unidentified funds out of deposits, and cannot close with open exceptions', function () {
    $world = keuWorld();
    $tx = portalAsTenant($world['university'], fn () => PaymentTransaction::query()->create([
        'transaction_number' => 'TRX-TEST-0001', 'student_id' => $world['student']->id, 'provider' => 'manual', 'method' => 'manual_transfer',
        'target_type' => 'deposit', 'amount' => 750000, 'status' => 'success', 'paid_at' => now(), 'reconciliation_status' => 'unreconciled',
    ]));
    $officer = keuStaff($world, ['reconciliations.read', 'reconciliations.create', 'reconciliations.update']);
    $closer = keuStaff($world, ['reconciliations.finalize']);
    $headers = portalHeaders($world);

    $batch = $this->actingAs($officer)->withHeaders($headers)
        ->postJson('/api/v1/finance/reconciliations', ['source' => 'bank_statement', 'statement_date' => now()->toDateString()])->assertApiSuccess(201)->json('data.id');
    $this->postJson("/api/v1/finance/reconciliations/{$batch}/import", ['rows' => [
        ['date' => now()->toDateString(), 'amount' => 750000, 'reference' => 'TRX-TEST-0001', 'description' => 'TRF MHS'],
        ['date' => now()->toDateString(), 'amount' => 125000, 'description' => 'SETORAN TANPA BERITA'],
    ]])->assertApiSuccess();

    expect($tx->fresh()->reconciliation_status)->toBe(ReconciliationStatus::Matched);
    $items = $this->getJson("/api/v1/finance/reconciliations/{$batch}")->assertApiSuccess()->json('data.items');
    $unidentified = collect($items)->firstWhere('result', 'unidentified');
    expect($unidentified)->not->toBeNull()
        ->and(PaymentTransaction::query()->count())->toBe(1);

    $this->actingAs($closer)->withHeaders($headers)->postJson("/api/v1/finance/reconciliations/{$batch}/close")->assertApiError(409)
        ->assertJsonPath('errors.code', 'BATCH_HAS_OPEN_ITEMS');

    $this->actingAs($officer)->withHeaders($headers)->postJson("/api/v1/finance/reconciliation-items/{$unidentified['id']}/resolve", [
        'resolution' => 'ignored', 'note' => 'Setoran internal unit kantin, bukan pembayaran mahasiswa',
    ])->assertApiSuccess();
    $this->actingAs($closer)->withHeaders($headers)->postJson("/api/v1/finance/reconciliations/{$batch}/close")->assertApiSuccess()->assertJsonPath('data.status', 'closed');

    // Baris yang sama di batch lain terdeteksi sebagai duplikat.
    $again = $this->actingAs($officer)->withHeaders($headers)
        ->postJson('/api/v1/finance/reconciliations', ['source' => 'bank_statement', 'statement_date' => now()->toDateString()])->json('data.id');
    $this->postJson("/api/v1/finance/reconciliations/{$again}/import", ['rows' => [['date' => now()->toDateString(), 'amount' => 750000, 'reference' => 'TRX-TEST-0001']]])
        ->assertApiSuccess();
    expect(collect($this->getJson("/api/v1/finance/reconciliations/{$again}")->json('data.items'))->pluck('result')->all())->toBe(['duplicate']);
});

test('a finance block stops KRS until a dispensation is granted and is revoked automatically once paid', function () {
    $world = keuWorld();
    $invoice = keuInvoice($world, '2000000');
    portalAsTenant($world['university'], fn () => FinanceClearanceRule::query()->create([
        'code' => 'KEU-KRS', 'name' => 'Lunas tagihan registrasi sebelum KRS', 'services' => ['krs'], 'requirement' => 'fully_paid',
        'bill_groups' => ['registration'], 'terms' => 'current', 'grace_days' => 0, 'min_outstanding_amount' => 0, 'include_penalties' => false, 'is_active' => true,
    ]));
    $classSection = portalClass($world, portalCourse($world));
    $officer = keuStaff($world, ['finance_clearance.read', 'finance_clearance.create', 'finance_clearance.update']);
    $headers = portalHeaders($world);

    $this->actingAs($officer)->withHeaders($headers)->postJson('/api/v1/finance/clearance/evaluate', ['student_id' => $world['student']->id])
        ->assertApiSuccess()->assertJsonPath('data.0.blocked', true);
    $block = AcademicBlock::query()->sole();
    expect($block->status)->toBe(AcademicBlockStatus::Active)->and($block->source)->toBe('finance');

    $this->actingAs($world['user'])->withHeaders($headers)->getJson('/api/v1/student/finance/summary')
        ->assertApiSuccess()->assertJsonPath('data.services.0.service', 'krs')->assertJsonPath('data.services.0.blocked', true);
    $this->postJson('/api/v1/student/krs/items', ['class_section_id' => $classSection->id])->assertApiError(409)
        ->assertJsonPath('errors.code', 'ACADEMIC_BLOCKED');

    // Dispensasi KRS 3 hari oleh petugas (≤ batas petugas) → langsung aktif.
    $this->actingAs($officer)->withHeaders($headers)->postJson("/api/v1/finance/blocks/{$block->id}/dispensations", [
        'service' => 'krs', 'valid_until' => now()->addDays(3)->toIso8601String(), 'reason' => 'Janji bayar setelah panen orang tua',
    ])->assertApiSuccess(201)->assertJsonPath('data.status', 'active');
    $this->actingAs($world['user'])->withHeaders($headers)->postJson('/api/v1/student/krs/items', ['class_section_id' => $classSection->id])->assertApiSuccess(201);

    // Lunas → cekal dicabut otomatis (listener), riwayat tercatat.
    $cashier = keuCashier($world);
    keuPayCash($world, $cashier, keuOpenSession($world, $cashier), $invoice->id, 2000000)->assertApiSuccess(201);

    expect($block->fresh()->status)->toBe(AcademicBlockStatus::Revoked)
        ->and(AcademicBlockHistory::query()->where('academic_block_id', $block->id)->pluck('action')->all())->toContain('imposed', 'exempted', 'revoked');
});

test('a dispensation longer than the staff limit waits for an official who is not the requester', function () {
    $world = keuWorld();
    keuInvoice($world, '100000');
    portalAsTenant($world['university'], fn () => FinanceClearanceRule::query()->create([
        'code' => 'KEU-UJIAN', 'name' => 'Lunas sebelum ujian', 'services' => ['exam'], 'requirement' => 'fully_paid', 'terms' => 'all',
        'grace_days' => 0, 'min_outstanding_amount' => 0, 'include_penalties' => true, 'is_active' => true,
    ]));
    $officer = keuStaff($world, ['finance_clearance.read', 'finance_clearance.create', 'finance_clearance.update']);
    $official = keuStaff($world, ['finance_clearance.approve']);
    $headers = portalHeaders($world);

    $this->actingAs($officer)->withHeaders($headers)->postJson('/api/v1/finance/clearance/evaluate', ['student_id' => $world['student']->id])->assertApiSuccess();
    $block = AcademicBlock::query()->sole();

    $exemption = $this->postJson("/api/v1/finance/blocks/{$block->id}/dispensations", [
        'service' => 'exam', 'valid_until' => now()->addDays(14)->toIso8601String(), 'reason' => 'Musibah keluarga, dokumen terlampir',
    ])->assertApiSuccess(201)->assertJsonPath('data.status', 'pending')->json('data.id');
    $this->postJson("/api/v1/finance/blocks/{$block->id}/dispensations", [
        'service' => 'exam', 'valid_until' => now()->addDays(60)->toIso8601String(), 'reason' => 'Melebihi batas maksimal dispensasi',
    ])->assertApiError(409);

    $this->actingAs($official)->withHeaders($headers)->postJson("/api/v1/finance/dispensations/{$exemption}/decide", ['approve' => true, 'note' => 'Disetujui Kabag'])
        ->assertApiSuccess()->assertJsonPath('data.status', 'active');
    expect($block->fresh()->status)->toBe(AcademicBlockStatus::Exempted);
});

test('students only ever reach their own finance data', function () {
    $world = keuWorld();
    $other = keuOtherStudentUser($world);
    $mine = keuInvoice($world, '100000');
    $theirs = keuInvoice($world, '200000', $other['student']);
    $theirTx = portalAsTenant($world['university'], fn () => PaymentTransaction::query()->create([
        'transaction_number' => 'TRX-OTHER', 'student_id' => $other['student']->id, 'provider' => 'cash', 'method' => 'cash', 'target_type' => 'invoice',
        'target_id' => $theirs->id, 'amount' => 1000, 'status' => 'success', 'paid_at' => now(), 'receipt_number' => 'KWT-OTHER', 'reconciliation_status' => 'pending_deposit',
    ]));
    $headers = portalHeaders($world);

    $this->actingAs($world['user'])->withHeaders($headers)->getJson('/api/v1/student/finance/invoices')
        ->assertApiSuccess()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $mine->id);
    $this->getJson("/api/v1/student/finance/invoices/{$theirs->id}")->assertApiError(404);
    $this->getJson("/api/v1/student/finance/transactions/{$theirTx->id}/receipt")->assertApiError(404);
    $this->postJson("/api/v1/student/finance/invoices/{$theirs->id}/deposit")->assertApiError(404);
    $this->postJson('/api/v1/student/finance/payments/manual', ['target_type' => 'invoice', 'target_id' => $theirs->id])->assertApiError(409);
    $this->postJson("/api/v1/student/finance/transactions/{$theirTx->id}/cancel")->assertApiError(404);

    // Endpoint admin tertutup bagi mahasiswa.
    $this->getJson('/api/v1/finance/dashboard')->assertApiError(403);
    $this->getJson("/api/v1/invoices/{$theirs->id}")->assertApiError(403);
    $this->getJson('/api/v1/finance/reports/receivables')->assertApiError(403);
});

test('a user from another university cannot read this tenant finance data', function () {
    $world = keuWorld();
    $invoice = keuInvoice($world);
    $outsider = keuWorld();
    $staff = keuStaff($outsider, ['invoices.read', 'finance_dashboard.read']);

    $this->actingAs($staff)->withHeaders(portalHeaders($world))->getJson("/api/v1/invoices/{$invoice->id}")->assertForbidden();
    $this->withHeaders(portalHeaders($outsider))->getJson("/api/v1/invoices/{$invoice->id}")->assertNotFound();
});

test('dashboard, receivables report and student summary agree on outstanding amounts', function () {
    $world = keuWorld();
    $other = keuOtherStudentUser($world);
    $a = keuInvoice($world, '1000000');
    keuInvoice($world, '400000', dueDate: CarbonImmutable::today()->subDays(10)); // lewat tenggang default 3 hari
    keuInvoice($world, '250000', $other['student']);
    $cashier = keuCashier($world);
    keuPayCash($world, $cashier, keuOpenSession($world, $cashier), $a->id, 300000)->assertApiSuccess(201);
    $manager = keuStaff($world, ['finance_dashboard.read', 'finance_reports.read', 'finance_reports.export']);
    $headers = portalHeaders($world);

    // 700.000 + 400.000 (mahasiswa A) + 250.000 (mahasiswa B) = 1.350.000; tunggakan 400.000.
    $this->actingAs($manager)->withHeaders($headers)->getJson('/api/v1/finance/dashboard')->assertApiSuccess()
        ->assertJsonPath('data.summary.receivable', '1350000.00')->assertJsonPath('data.summary.arrears', '400000.00')
        ->assertJsonPath('data.summary.received', '300000.00');

    $report = $this->getJson('/api/v1/finance/reports/receivables')->assertApiSuccess()->json('data');
    expect($report['summary']['total_outstanding'])->toBe('1350000.00')
        ->and($report['summary']['total_arrears'])->toBe('400000.00')
        ->and($report['total_rows'])->toBe(2);

    $this->getJson('/api/v1/finance/reports/receivables?study_program_id='.$world['program']->id.'&search='.urlencode($world['student']->nim))
        ->assertApiSuccess()->assertJsonPath('data.summary.total_outstanding', '1100000.00');

    $this->get('/api/v1/finance/reports/receivables/export')->assertStatus(422);
    $csv = $this->get('/api/v1/finance/reports/receivables/export?reason='.urlencode('Rapat pimpinan bulanan'))->assertOk();
    expect($csv->headers->get('content-type'))->toContain('text/csv')
        ->and($csv->streamedContent())->toContain($world['student']->nim);
    $this->getJson('/api/v1/finance/reports/unknown-type')->assertNotFound();

    $this->actingAs($world['user'])->withHeaders($headers)->getJson('/api/v1/student/finance/summary')
        ->assertApiSuccess()->assertJsonPath('data.outstanding', '1100000.00')->assertJsonPath('data.arrears', '400000.00');
});

test('a finance exam block also stops the public NIM-based exam link', function () {
    $world = keuWorld();
    $classSection = portalClass($world, portalCourse($world));
    $exam = portalAsTenant($world['university'], fn () => Exam::factory()->create([
        'university_id' => $world['university']->id, 'class_section_id' => $classSection->id, 'access_token' => 'tok-public-exam', 'is_published' => true,
    ]));
    portalAsTenant($world['university'], fn () => app(AcademicBlockService::class)
        ->impose($world['student'], 'finance', 'KEU-UJIAN', 'financial', ['exam'], 'Kewajiban keuangan belum terpenuhi.'));

    $this->app['auth']->forgetGuards();
    $this->postJson("/api/v1/public/exams/{$exam->access_token}/access", ['nim' => $world['student']->nim])
        ->assertApiError(409)->assertJsonPath('errors.code', 'ACADEMIC_BLOCKED');
    expect(ExamAttempt::query()->count())->toBe(0);
});
