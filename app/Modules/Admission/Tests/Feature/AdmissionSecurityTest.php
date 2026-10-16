<?php

require_once __DIR__.'/../Support/helpers.php';

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Admission\Models\Application;
use Modules\Admission\Models\PaymentCallback;
use Modules\Admission\Models\PmbInvoice;
use Modules\Admission\Models\SelectionRun;
use Modules\Admission\Payments\PaymentGatewayManager;
use Modules\Admission\Tests\Support\PmbWorld;
use Modules\UserManagement\Models\Role;
use Modules\UserManagement\Models\UserRole;
use Modules\UserManagement\Support\PermissionRegistry;

beforeEach(function (): void {
    Storage::fake(config('pmb.storage_disk', 'local'));
    config(['pmb.captcha.driver' => 'none']);
});

it('isolates applicants from each other and from other tenants', function (): void {
    $w = PmbWorld::create($this);
    $cfg = pmbConfigure($w);
    $tokenA = pmbApplicantToken($w, 'a@example.test', 'Ani Wijaya');
    $tokenB = pmbApplicantToken($w, 'b@example.test', 'Bayu Pratama');
    $appA = pmbOk($w->asApplicant($tokenA, 'POST', 'applications', ['wave_path_id' => $cfg['wavePath']], ['Idempotency-Key' => (string) Str::uuid()]))->json('data.id');

    // Pendaftar lain: 404 identik (tidak membocorkan keberadaan).
    $w->asApplicant($tokenB, 'GET', "applications/{$appA}")->assertStatus(404);
    $w->asApplicant($tokenB, 'GET', "applications/{$appA}/documents")->assertStatus(404);
    $w->asApplicant($tokenB, 'DELETE', "applications/{$appA}")->assertStatus(404);

    // Staf tenant lain tidak melihat aplikasi tenant ini, walau memegang izin penuh.
    $other = PmbWorld::create($this, 'ULAIN');
    $other->asStaff('admission_administrator', 'GET', "applications/{$appA}")->assertStatus(404);
    expect($other->asStaff('admission_administrator', 'GET', 'applications')->json('meta.total'))->toBe(0);

    // Token pendaftar tidak diterima di tenant lain (tenant diikat dari akun).
    $this->flushHeaders();
    app('auth')->forgetGuards();
    $this->withHeaders(['Authorization' => 'Bearer '.$tokenA])->getJson('/api/v1/pmb/applicant/me', ['X-University-ID' => $other->university->getKey()])->assertStatus(401);
});

it('enforces RBAC and actor types on admin and applicant endpoints', function (): void {
    $w = PmbWorld::create($this);
    $cfg = pmbConfigure($w);
    $token = pmbApplicantToken($w, 'c@example.test', 'Cahya Putri');

    // Token pendaftar tidak dapat memakai endpoint staf.
    $this->flushHeaders();
    app('auth')->forgetGuards();
    $this->withHeaders(['Authorization' => 'Bearer '.$token])->getJson('/api/v1/pmb/admin/applications', ['X-University-ID' => $w->university->getKey()])->assertStatus(403);

    // Staf tidak dapat memakai portal pendaftar.
    app('auth')->forgetGuards();
    $this->flushHeaders();
    $this->actingAs($w->staff['admission_administrator'])->getJson('/api/v1/pmb/applicant/me', ['X-University-ID' => $w->university->getKey()])->assertStatus(401);

    // Matriks permission (docs/pmb/08 §2).
    $w->asStaff('admission_verifier', 'POST', 'periods', ['code' => 'X'])->assertStatus(403);
    $w->asStaff('admission_verifier', 'POST', 'selection-runs', ['wave_path_id' => $cfg['wavePath']])->assertStatus(403);
    $draft = pmbOk($w->asApplicant($token, 'POST', 'applications', ['wave_path_id' => $cfg['wavePath']], ['Idempotency-Key' => (string) Str::uuid()]))->json('data.id');
    $w->asStaff('finance_administrator', 'POST', "applications/{$draft}/verify", ['lock_version' => 0])->assertStatus(403);
    $w->asStaff('admission_selection_committee', 'GET', 'manual-proofs')->assertStatus(403);
    $w->asStaff('admission_approver', 'GET', 'audit-logs')->assertStatus(403);
    $w->asStaff('admission_registrar', 'PUT', 'nim-settings', ['format' => '{YY}{SEQ:4}'])->assertStatus(403);
    $w->asStaff('admission_administrator', 'GET', 'audit-logs')->assertOk();

    $lecturer = $w->staffWithRole('lecturer');
    $w->asStaff($lecturer, 'GET', 'applications')->assertStatus(403);
    $w->asStaff($lecturer, 'GET', 'examiner/sessions')->assertOk();
});

it('scopes faculty and program staff to applications choosing their programs', function (): void {
    $w = PmbWorld::create($this);
    $cfg = pmbConfigure($w);
    $tokenA = pmbApplicantToken($w, 'd@example.test', 'Dewi Anggraini');
    $tokenB = pmbApplicantToken($w, 'e@example.test', 'Eko Saputra');
    $appA = pmbSubmitApplication($w, $tokenA, $cfg['wavePath'], 85);
    $appB = pmbSubmitApplication($w, $tokenB, $cfg['wavePath'], 85, $w->otherProgram->getKey());

    $head = $w->staffWithRole('head_of_study_program', ['type' => 'study_program', 'id' => $w->program->getKey()]);
    $visible = collect(pmbOk($w->asStaff($head, 'GET', 'applications'))->json('data'))->pluck('id');
    expect($visible->all())->toBe([$appA]);
    $w->asStaff($head, 'GET', "applications/{$appB}")->assertStatus(404);
    $w->asStaff($head, 'GET', "applications/{$appA}")->assertOk();
});

it('replays idempotent requests and rejects reused keys with a different body', function (): void {
    $w = PmbWorld::create($this);
    $cfg = pmbConfigure($w);
    $token = pmbApplicantToken($w, 'f@example.test', 'Fajar Nugroho');
    $key = (string) Str::uuid();

    $first = pmbOk($w->asApplicant($token, 'POST', 'applications', ['wave_path_id' => $cfg['wavePath']], ['Idempotency-Key' => $key]));
    $second = pmbOk($w->asApplicant($token, 'POST', 'applications', ['wave_path_id' => $cfg['wavePath']], ['Idempotency-Key' => $key]));
    expect($second->json('data.id'))->toBe($first->json('data.id'));
    expect(Application::query()->withoutGlobalScopes()->count())->toBe(1);

    $w->asApplicant($token, 'POST', 'applications', ['wave_path_id' => $cfg['period']], ['Idempotency-Key' => $key])
        ->assertStatus(422)->assertJsonPath('errors.code', 'IDEMPOTENCY_KEY_REUSED');

    // BR-04: aplikasi aktif kedua pada periode yang sama ditolak.
    $w->asApplicant($token, 'POST', 'applications', ['wave_path_id' => $cfg['wavePath']], ['Idempotency-Key' => (string) Str::uuid()])->assertStatus(409);
});

it('forbids self-approval of a selection run (separation of duties)', function (): void {
    $w = PmbWorld::create($this);
    $cfg = pmbConfigure($w);
    $token = pmbApplicantToken($w, 'g@example.test', 'Gita Maharani');
    $app = pmbSubmitApplication($w, $token, $cfg['wavePath'], 88);
    pmbPayAndVerify($w, $token, $app);

    // Anggota panitia yang juga Admin PMB mengajukan run, lalu mencoba menyetujui langkah 1.
    $committee = $w->staff['admission_selection_committee'];
    UserRole::query()->create(['user_id' => $committee->getKey(), 'role_id' => Role::query()->where('slug', 'admission_administrator')->whereNull('university_id')->value('id'), 'university_id' => $w->university->getKey(), 'assigned_at' => now()]);
    PermissionRegistry::flushAll();

    $runId = pmbOk($w->asStaff($committee, 'POST', 'selection-runs', ['wave_path_id' => $cfg['wavePath']]))->json('data.id');
    pmbOk($w->asStaff($committee, 'POST', "selection-runs/{$runId}/review"));
    pmbOk($w->asStaff($committee, 'POST', "selection-runs/{$runId}/submit"));

    $approvalId = SelectionRun::query()->withoutGlobalScopes()->find($runId)->approval_request_id;
    pmbApproveCurrentStep($w, $this, $approvalId, $committee)->assertStatus(409)->assertJsonPath('errors.code', 'SELF_APPROVAL_FORBIDDEN');
    pmbOk(pmbApproveCurrentStep($w, $this, $approvalId, $w->staff['admission_administrator']));

    // Hasil yang sudah disetujui tidak dapat diubah lagi (snapshot terkunci).
    expect(SelectionRun::query()->withoutGlobalScopes()->find($runId)->status->value)->toBe('pending_approval');
});

it('verifies Xendit callbacks and applies each payment exactly once', function (): void {
    config(['services.xendit.secret_key' => 'xnd_development_TEST', 'services.xendit.webhook_token' => 'cb-token-TEST']);
    $this->app->forgetInstance(PaymentGatewayManager::class);

    $w = PmbWorld::create($this);
    $cfg = pmbConfigure($w);
    pmbOk($w->asStaff('admission_administrator', 'PUT', 'settings', ['values' => ['pmb.payment.channels' => [
        ['code' => 'va_bca', 'label' => 'Virtual Account BCA', 'provider' => 'xendit', 'admin_fee' => '0.00', 'fee_borne_by' => 'campus', 'sort' => 1, 'is_active' => true],
    ]]]));

    $token = pmbApplicantToken($w, 'h@example.test', 'Hadi Kusuma');
    $app = pmbSubmitApplication($w, $token, $cfg['wavePath'], 80);
    $invoice = pmbOk($w->asApplicant($token, 'GET', "applications/{$app}/invoices"))->json('data.0');

    $settled = false;
    Http::fake(function (Request $request) use (&$settled) {
        if ($request->method() === 'POST' && str_ends_with($request->url(), '/v2/invoices')) {
            return Http::response(['id' => 'inv-pmb-1', 'external_id' => $request['external_id'], 'status' => 'PENDING', 'amount' => $request['amount'], 'currency' => 'IDR',
                'invoice_url' => 'https://checkout-staging.xendit.co/web/inv-pmb-1', 'expiry_date' => now()->addDay()->toIso8601ZuluString(),
                'available_banks' => [['bank_code' => 'BCA', 'collection_type' => 'POOL', 'bank_account_number' => '12345678901']]]);
        }

        return Http::response(['id' => 'inv-pmb-1', 'status' => $settled ? 'PAID' : 'PENDING', 'amount' => 150000, 'paid_amount' => 150000, 'currency' => 'IDR', 'paid_at' => now()->toIso8601ZuluString()]);
    });

    $tx = pmbOk($w->asApplicant($token, 'POST', "invoices/{$invoice['id']}/transactions", ['channel' => 'va_bca'], ['Idempotency-Key' => (string) Str::uuid()]))->json('data.transaction');
    expect($tx['va_number'])->toBe('12345678901')
        ->and($tx['redirect_url'])->toBe('https://checkout-staging.xendit.co/web/inv-pmb-1')
        ->and($tx['status'])->toBe('pending');
    Http::assertSent(fn (Request $r) => str_ends_with($r->url(), '/v2/invoices') && $r['payment_methods'] === ['BCA'] && $r['amount'] === 150000
        && $r['external_id'] === 'PMB-'.$tx['id'] && $r->hasHeader('Authorization', 'Basic '.base64_encode('xnd_development_TEST:')));

    $payload = ['id' => 'inv-pmb-1', 'external_id' => 'PMB-'.$tx['id'], 'status' => 'PAID', 'amount' => 150000, 'paid_amount' => 150000, 'currency' => 'IDR', 'paid_at' => now()->toIso8601ZuluString()];

    // Token callback salah/tidak ada → 401, tidak mengubah apa pun.
    $this->flushHeaders();
    $this->postJson('/api/v1/webhooks/xendit', $payload, ['x-callback-token' => 'salah'])->assertStatus(401);
    $this->postJson('/api/v1/webhooks/xendit', $payload)->assertStatus(401);

    // Endpoint bersama & endpoint modul menerima callback yang sama → diterapkan sekali.
    $settled = true;
    $this->postJson('/api/v1/webhooks/xendit', $payload, ['x-callback-token' => 'cb-token-TEST'])->assertOk();
    $this->postJson('/api/v1/pmb/webhooks/payments/xendit', $payload, ['x-callback-token' => 'cb-token-TEST'])->assertOk();

    $invoiceModel = PmbInvoice::query()->withoutGlobalScopes()->findOrFail($invoice['id']);
    expect($invoiceModel->status->value)->toBe('paid')->and((string) $invoiceModel->amount_paid)->toBe('150000.00');
    expect(PaymentCallback::query()->withoutGlobalScopes()->where('signature_valid', true)->count())->toBe(1);
    expect(Application::query()->withoutGlobalScopes()->find($app)->registration_payment_status->value)->toBe('paid');
});

it('promotes the waitlist when an accepted applicant declines', function (): void {
    $w = PmbWorld::create($this);
    $cfg = pmbConfigure($w);
    $tokenA = pmbApplicantToken($w, 'i@example.test', 'Indah Permata');
    $tokenB = pmbApplicantToken($w, 'j@example.test', 'Joko Susilo');
    $appA = pmbSubmitApplication($w, $tokenA, $cfg['wavePath'], 92);
    $appB = pmbSubmitApplication($w, $tokenB, $cfg['wavePath'], 75);
    pmbPayAndVerify($w, $tokenA, $appA);
    pmbPayAndVerify($w, $tokenB, $appB);

    $runId = pmbOk($w->asStaff('admission_selection_committee', 'POST', 'selection-runs', ['wave_path_id' => $cfg['wavePath']]))->json('data.id');
    pmbOk($w->asStaff('admission_selection_committee', 'POST', "selection-runs/{$runId}/review"));
    pmbOk($w->asStaff('admission_selection_committee', 'POST', "selection-runs/{$runId}/submit"));
    $approvalId = SelectionRun::query()->withoutGlobalScopes()->find($runId)->approval_request_id;
    pmbOk(pmbApproveCurrentStep($w, $this, $approvalId, $w->staff['admission_administrator']));
    pmbOk(pmbApproveCurrentStep($w, $this, $approvalId, $w->staff['admission_approver']));

    $this->travel(10)->days();
    $this->travel(1)->minutes();
    $tokenA = pmbOk($w->asPublic('POST', 'applicant/auth/login', ['login' => 'i@example.test', 'password' => 'Rahasia-Kuat-2027!']))->json('data.token');
    $tokenB = pmbOk($w->asPublic('POST', 'applicant/auth/login', ['login' => 'j@example.test', 'password' => 'Rahasia-Kuat-2027!']))->json('data.token');
    expect(pmbOk($w->asApplicant($tokenB, 'GET', "applications/{$appB}/result"))->json('data.decision'))->toBe('waitlisted');

    pmbOk($w->asApplicant($tokenA, 'POST', "applications/{$appA}/re-registration/decline", ['reason' => 'Memilih kampus lain', 'confirm' => true], ['Idempotency-Key' => (string) Str::uuid()]));

    $seats = collect(pmbOk($w->asStaff('admission_administrator', 'GET', "periods/{$cfg['period']}/seats"))->json('data'))->firstWhere('program', $w->program->name);
    expect($seats['free_seats'])->toBe(1)->and($seats['waitlist_remaining'])->toBe(1);

    pmbOk($w->asStaff('admission_administrator', 'POST', "quotas/{$seats['quota_id']}/promote-waitlist", ['count' => 1], ['Idempotency-Key' => (string) Str::uuid()]));
    expect(pmbOk($w->asApplicant($tokenB, 'GET', "applications/{$appB}/result"))->json('data.decision'))->toBe('accepted');

    // Promosi kedua tidak melebihi kapasitas.
    $w->asStaff('admission_administrator', 'POST', "quotas/{$seats['quota_id']}/promote-waitlist", ['count' => 1], ['Idempotency-Key' => (string) Str::uuid()])->assertStatus(409);
});

it('lists only same-tenant staff holding a PMB permission for assignment pickers', function (): void {
    $w = PmbWorld::create($this);
    $other = PmbWorld::create($this, 'ULAIN2');

    $verifiers = collect(pmbOk($w->asStaff('admission_administrator', 'GET', 'staff', ['permission' => 'pmb_verification.approve']))->json('data'))->pluck('id');
    expect($verifiers)->toContain($w->staff['admission_verifier']->getKey(), $w->staff['admission_administrator']->getKey())
        ->not->toContain($other->staff['admission_verifier']->getKey())
        ->not->toContain($w->staff['finance_administrator']->getKey());

    // Slug non-PMB ditolak: endpoint bukan pemetaan RBAC umum.
    $w->asStaff('admission_administrator', 'GET', 'staff', ['permission' => 'users.read'])->assertStatus(422);
    $w->asStaff('finance_administrator', 'GET', 'staff', ['permission' => 'pmb_verification.approve'])->assertOk();
});

