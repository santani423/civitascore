<?php

require_once __DIR__.'/../Support/helpers.php';

use App\Support\Tenancy\TenantContext;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Academic\Models\Student;
use Modules\Admission\Models\Application;
use Modules\Admission\Models\SelectionRun;
use Modules\Admission\Models\StudentConversion;
use Modules\Admission\Tests\Support\PmbWorld;
use Modules\ApprovalWorkflow\Models\ApprovalRequest;
use Modules\ApprovalWorkflow\Models\ApprovalRequestStep;
use Modules\Finance\Models\Invoice;

beforeEach(function (): void {
    Storage::fake(config('pmb.storage_disk', 'local'));
    config(['pmb.captcha.driver' => 'none']);
});

it('runs the full admission lifecycle from configuration to student enrollment', function (): void {
    $w = PmbWorld::create($this);
    $cfg = pmbConfigure($w);

    // Website publik menampilkan periode & jalur yang dibuka.
    expect(pmbOk($w->asPublic('GET', 'public/periods/active'))->json('data.0.code'))->toBe('PMB27');

    // Dua pendaftar bersaing untuk 1 kursi TI: A (rapor 90) diterima, B (80) cadangan.
    $tokenA = pmbApplicantToken($w, 'budi@example.test');
    $tokenB = pmbApplicantToken($w, 'citra@example.test', 'Citra Lestari');
    $appA = pmbSubmitApplication($w, $tokenA, $cfg['wavePath'], 90);
    $appB = pmbSubmitApplication($w, $tokenB, $cfg['wavePath'], 80);

    // Pembayaran biaya pendaftaran via transfer manual → diverifikasi keuangan.
    foreach ([[$tokenA, $appA], [$tokenB, $appB]] as [$token, $appId]) {
        $invoice = pmbOk($w->asApplicant($token, 'GET', "applications/{$appId}/invoices"))->json('data.0');
        expect($invoice['type'])->toBe('registration_fee')->and($invoice['amount_net'])->toBe('150000.00');
        $tx = pmbOk($w->asApplicant($token, 'POST', "invoices/{$invoice['id']}/transactions", ['channel' => 'manual_transfer'], ['Idempotency-Key' => (string) Str::uuid()]))->json('data.transaction');
        pmbOk($w->asApplicant($token, 'POST', "transactions/{$tx['id']}/manual-proof", [
            'file' => UploadedFile::fake()->image('bukti.jpg'), 'sender_bank' => 'BCA', 'sender_name' => 'Budi', 'transfer_date' => now()->toDateString(), 'transferred_amount' => $tx['amount'],
        ], ['Idempotency-Key' => (string) Str::uuid()]));
    }

    foreach (pmbOk($w->asStaff('finance_administrator', 'GET', 'manual-proofs'))->json('data') as $proof) {
        pmbOk($w->asStaff('finance_administrator', 'POST', "manual-proofs/{$proof['id']}/decision", ['approve' => true], ['Idempotency-Key' => (string) Str::uuid()]));
    }

    expect(Application::query()->withoutGlobalScopes()->find($appA)->registration_payment_status->value)->toBe('paid');

    // Verifikasi: klaim, setujui dokumen, verifikasi aplikasi.
    foreach ([$appA, $appB] as $appId) {
        pmbOk($w->asStaff('admission_verifier', 'POST', "applications/{$appId}/claim"));
        $docs = pmbOk($w->asStaff('admission_verifier', 'GET', "applications/{$appId}/documents"))->json('data.documents');

        foreach ($docs as $doc) {
            pmbOk($w->asStaff('admission_verifier', 'POST', "application-documents/{$doc['id']}/reviews", ['decision' => 'approved', 'version_id' => $doc['current_version_id']]));
        }

        $lockVersion = pmbOk($w->asStaff('admission_verifier', 'GET', "applications/{$appId}"))->json('data.lock_version');
        pmbOk($w->asStaff('admission_verifier', 'POST', "applications/{$appId}/verify", ['lock_version' => $lockVersion]));
    }

    // Seleksi: run → tinjau → ajukan → disetujui 2 langkah (Admin PMB → Pengesah).
    $runId = pmbOk($w->asStaff('admission_selection_committee', 'POST', 'selection-runs', ['wave_path_id' => $cfg['wavePath']]))->json('data.id');
    $run = pmbOk($w->asStaff('admission_selection_committee', 'GET', "selection-runs/{$runId}"))->json('data');
    expect($run['status'])->toBe('computed');

    $results = collect(pmbOk($w->asStaff('admission_selection_committee', 'GET', "selection-runs/{$runId}/results"))->json('data'))->keyBy('application_id');
    expect($results[$appA]['effective_recommendation'])->toBe('pass')
        ->and($results[$appB]['effective_recommendation'])->toBe('waitlist')
        ->and((float) $results[$appA]['final_score'])->toBe(90.0);

    pmbOk($w->asStaff('admission_selection_committee', 'POST', "selection-runs/{$runId}/review"));
    pmbOk($w->asStaff('admission_selection_committee', 'POST', "selection-runs/{$runId}/submit", ['note' => 'Hasil gelombang 1']));

    foreach (['admission_administrator', 'admission_approver'] as $approver) {
        $w->tenant();
        $step = ApprovalRequestStep::query()->findOrFail(ApprovalRequest::query()->findOrFail(SelectionRun::query()->find($runId)->approval_request_id)->current_step_id);
        expect($step->workflowStep->approverRole->slug)->toBe($approver);
        app(TenantContext::class)->setUniversityId(null);
        app('auth')->forgetGuards();
        $this->flushHeaders();
        pmbOk($this->actingAs($w->staff[$approver])->postJson("/api/v1/approval-request-steps/{$step->getKey()}/approve", ['comment' => 'Setuju'], ['X-University-ID' => $w->university->getKey()]));
    }

    expect(SelectionRun::query()->withoutGlobalScopes()->find($runId)->status->value)->toBe('approved');

    // Pengumuman: dijadwalkan; sebelum waktunya pendaftar tidak dapat melihat hasil.
    // Pengesahan otomatis menjadwalkan batch pada announcement_at gelombang; jadwal ganda ditolak.
    $batches = pmbOk($w->asStaff('admission_administrator', 'GET', 'announcement-batches'))->json('data');
    expect($batches)->toHaveCount(1)->and($batches[0]['status'])->toBe('scheduled');
    $w->asStaff('admission_administrator', 'POST', "selection-runs/{$runId}/announcement-batches", ['publish_at' => now()->addDays(11)->toIso8601String()])->assertStatus(409);
    $w->asApplicant($tokenA, 'GET', "applications/{$appA}/result")->assertStatus(404);

    $this->travel(10)->days();
    $this->travel(1)->minutes();
    // Token 12 jam sudah kedaluwarsa setelah 10 hari → wajib login ulang.
    $w->asApplicant($tokenA, 'GET', 'me')->assertStatus(401);
    $tokenA = pmbOk($w->asPublic('POST', 'applicant/auth/login', ['login' => 'budi@example.test', 'password' => 'Rahasia-Kuat-2027!']))->json('data.token');
    $tokenB = pmbOk($w->asPublic('POST', 'applicant/auth/login', ['login' => 'citra@example.test', 'password' => 'Rahasia-Kuat-2027!']))->json('data.token');
    $resultA = pmbOk($w->asApplicant($tokenA, 'GET', "applications/{$appA}/result"))->json('data');
    $resultB = pmbOk($w->asApplicant($tokenB, 'GET', "applications/{$appB}/result"))->json('data');
    expect($resultA['decision'])->toBe('accepted')->and($resultB['decision'])->toBe('waitlisted');

    // Daftar ulang: terima tawaran, lengkapi data, bayar biaya awal di loket, kirim.
    $reReg = pmbOk($w->asApplicant($tokenA, 'GET', "applications/{$appA}/re-registration"))->json('data');
    $accepted = pmbOk($w->asApplicant($tokenA, 'POST', "applications/{$appA}/re-registration/accept", ['declarations' => [], 'lock_version' => $reReg['lock_version']], ['Idempotency-Key' => (string) Str::uuid()]));
    expect($accepted->json('data.status'))->toBeIn(['confirmed', 'in_progress']);

    $reReg = pmbOk($w->asApplicant($tokenA, 'GET', "applications/{$appA}/re-registration"))->json('data');
    $saved = pmbOk($w->asApplicant($tokenA, 'PUT', "applications/{$appA}/re-registration/data", [
        'religion' => 'islam', 'mother_name' => 'Siti Aminah', 'nationality' => 'ID', 'address_line' => 'Jl. Merdeka 1', 'village' => 'Gambir',
        'district' => 'Gambir', 'city' => 'Jakarta Pusat', 'province' => 'DKI Jakarta', 'region_code' => '317101',
        'residence_type' => 'orang_tua', 'transportation' => 'motor', 'kps_recipient' => false, 'almamater_size' => 'L', 'lock_version' => $reReg['lock_version'],
    ]));

    $invoice = $reReg['invoice'];
    expect($invoice)->not->toBeNull()->and($invoice['amount_net'])->toBe('5000000.00');
    pmbOk($w->asStaff('finance_administrator', 'POST', "invoices/{$invoice['id']}/cashier-payments", ['amount' => 5000000, 'receipt_number' => 'KW-0001', 'paid_at' => now()->toIso8601String()], ['Idempotency-Key' => (string) Str::uuid()]));

    pmbOk($w->asApplicant($tokenA, 'POST', "applications/{$appA}/re-registration/submit", ['lock_version' => $saved->json('data.lock_version')]));

    // Petugas daftar ulang memeriksa dokumen asli lalu menyetujui → konversi.
    $reRegId = $reReg['id'];
    pmbOk($w->asStaff('admission_registrar', 'POST', "re-registrations/{$reRegId}/checks/ORIGINAL_DOCUMENTS_CHECKED", ['status' => 'passed']));
    $detail = pmbOk($w->asStaff('admission_registrar', 'GET', "re-registrations/{$reRegId}"))->json('data');
    $approved = pmbOk($w->asStaff('admission_registrar', 'POST', "re-registrations/{$reRegId}/approve", ['lock_version' => $detail['lock_version']], ['Idempotency-Key' => (string) Str::uuid()]));

    $conversion = StudentConversion::query()->withoutGlobalScopes()->findOrFail($approved->json('data.conversion_id'));
    expect($conversion->status->value)->toBe('succeeded')
        ->and($conversion->nim)->toStartWith(now()->format('y').'TI');

    $student = Student::query()->withoutGlobalScopes()->findOrFail($conversion->student_id);
    expect($student->admission_application_id)->toBe($appA)->and($student->nim)->toBe($conversion->nim);
    expect(Invoice::query()->withoutGlobalScopes()->where('source_id', $invoice['id'])->exists())->toBeTrue();

    $status = pmbOk($w->asApplicant($tokenA, 'GET', "applications/{$appA}/re-registration"))->json('data');
    expect($status['status'])->toBe('enrolled')->and($status['conversion']['nim'])->toBe($conversion->nim);
});
