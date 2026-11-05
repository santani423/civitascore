<?php

require_once __DIR__.'/../Support/helpers.php';
require_once __DIR__.'/../Support/assessment_helpers.php';

use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Modules\Admission\Enums\AssessmentSessionStatus;
use Modules\Admission\Models\AccountConsent;
use Modules\Admission\Models\AssessmentScore;
use Modules\Admission\Models\AssessmentSession;
use Modules\Admission\Tests\Support\PmbWorld;

beforeEach(function (): void {
    config(['pmb.captcha.driver' => 'none']);
});

afterEach(function (): void {
    Carbon::setTestNow();
});

it('menjalankan asesmen minat dari persetujuan sampai hasil dipublikasikan', function (): void {
    $w = PmbWorld::create($this);
    $cfg = pmbConfigure($w);
    $peserta = talentApplicant($w, $cfg, 'talenta@example.test');
    $instrument = talentPublished($w);
    $token = $peserta['token'];
    $appId = $peserta['application'];

    // Sebelum persetujuan: asesmen tampil tetapi belum bisa dimulai.
    $catalog = pmbOk($w->asApplicant($token, 'GET', "applications/{$appId}/assessments"))->json('data.assessments');
    expect($catalog)->toHaveCount(1)
        ->and($catalog[0]['can_start'])->toBeFalse()
        ->and($catalog[0]['blocked_reason'])->toBe('CONSENT_REQUIRED');

    $blocked = $w->asApplicant($token, 'POST', "applications/{$appId}/assessments/{$instrument['version']}/sessions");
    expect($blocked->status())->toBe(422)->and($blocked->json('errors.code'))->toBe('CONSENT_REQUIRED');

    pmbOk($w->asApplicant($token, 'POST', "applications/{$appId}/assessments/consent"));
    expect(AccountConsent::query()->withoutGlobalScopes()->where('consent_type', 'assessment_talent')->count())->toBe(1);

    $session = talentStart($w, $token, $appId, $instrument['version']);
    expect($session['status'])->toBe('in_progress')
        ->and($session['total_count'])->toBe(12)
        ->and($session['items'])->toHaveCount(12);

    // Dimensi dan arah butir tidak pernah dikirim ke peserta.
    expect($session['items'][0])->toHaveKeys(['id', 'stem', 'value'])->not->toHaveKeys(['dimension', 'is_reversed']);

    // Butir asing dan nilai di luar skala ditolak.
    $foreign = $w->asApplicant($token, 'PUT', "assessment-sessions/{$session['id']}/responses", [
        'responses' => [['item_id' => (string) Str::ulid(), 'value' => 3]],
    ]);
    expect($foreign->status())->toBe(422);

    $outOfScale = $w->asApplicant($token, 'PUT', "assessment-sessions/{$session['id']}/responses", [
        'responses' => [['item_id' => $instrument['items'][0]['id'], 'value' => 9]],
    ]);
    expect($outOfScale->status())->toBe(422);

    // Jawaban sebagian (koneksi putus di tengah), lalu kirim: ditolak sampai semua butir terjawab.
    $answers = talentAnswers($instrument['items']);
    pmbOk($w->asApplicant($token, 'PUT', "assessment-sessions/{$session['id']}/responses", ['responses' => array_slice($answers, 0, 5)]));

    $incomplete = $w->asApplicant($token, 'POST', "assessment-sessions/{$session['id']}/submit");
    expect($incomplete->status())->toBe(422)->and($incomplete->json('errors.code'))->toBe('UNANSWERED_ITEMS');

    // Kirim ulang seluruh jawaban (upsert, aman untuk pengiriman ulang), lalu kirim asesmen.
    $saved = pmbOk($w->asApplicant($token, 'PUT', "assessment-sessions/{$session['id']}/responses", ['responses' => $answers]))->json('data');
    expect($saved)->toMatchArray(['answered_count' => 12, 'total_count' => 12]);

    $submitted = pmbOk($w->asApplicant($token, 'POST', "assessment-sessions/{$session['id']}/submit"))->json('data');
    expect($submitted['status'])->toBe('submitted');

    // Hasil belum tampil sebelum admin mempublikasikan.
    $early = $w->asApplicant($token, 'GET', "assessment-sessions/{$session['id']}/result");
    expect($early->status())->toBe(409)->and($early->json('errors.code'))->toBe('RESULT_NOT_PUBLISHED');

    // Memulai lagi mengembalikan sesi yang sama; tidak ada sesi kedua.
    expect(talentStart($w, $token, $appId, $instrument['version'])['id'])->toBe($session['id']);

    pmbOk($w->asStaff('admission_approver', 'POST', "assessment-sessions/{$session['id']}/publish"));

    $result = pmbOk($w->asApplicant($token, 'GET', "assessment-sessions/{$session['id']}/result"))->json('data');
    expect($result['holland_code'])->toBe('RIA')
        ->and(array_column($result['dominant'], 'code'))->toBe(['R', 'I', 'A'])
        ->and($result['dimensions'])->toHaveCount(6)
        ->and($result['dimensions'][0])->toMatchArray(['code' => 'R', 'rank' => 1, 'item_count' => 2, 'mean_score' => 5, 'percent_score' => 100])
        ->and($result['dimensions'][0]['interpretation'])->toBe('Interpretasi R')
        ->and($result['disclaimer'])->toContain('Bukan diagnosis');

    // Setelah dipublikasikan, katalog menandai sesi sebagai selesai dan tidak bisa dimulai ulang.
    $after = pmbOk($w->asApplicant($token, 'GET', "applications/{$appId}/assessments"))->json('data.assessments.0');
    expect($after['session']['status'])->toBe('published')->and($after['can_start'])->toBeFalse();
});

it('mengunci versi setelah diajukan dan menolak publikasi sebelum disetujui', function (): void {
    $w = PmbWorld::create($this);
    $draft = talentDraft($w);
    $admin = 'admission_administrator';

    $noDimensions = $w->asStaff($admin, 'POST', "assessment-versions/{$draft['version']}/submit");
    expect($noDimensions->status())->toBe(422)->and($noDimensions->json('errors.code'))->toBe('DIMENSIONS_INCOMPLETE');

    $codeOutsideRiasec = $w->asStaff($admin, 'POST', "assessment-versions/{$draft['version']}/dimensions", ['code' => 'X', 'name' => 'Tidak dikenal']);
    expect($codeOutsideRiasec->status())->toBe(422)->and($codeOutsideRiasec->json('errors.code'))->toBe('INVALID_DIMENSION');

    foreach (['R', 'I', 'A', 'S', 'E', 'C'] as $code) {
        pmbOk($w->asStaff($admin, 'POST', "assessment-versions/{$draft['version']}/dimensions", ['code' => $code, 'name' => "Dimensi {$code}"]));
    }

    $noItems = $w->asStaff($admin, 'POST', "assessment-versions/{$draft['version']}/submit");
    expect($noItems->status())->toBe(422)->and($noItems->json('errors.code'))->toBe('ITEMS_INCOMPLETE');

    $dimensionR = $w->asStaff($admin, 'GET', "assessment-versions/{$draft['version']}")->json('data.dimensions.0.id');
    $item = (string) pmbOk($w->asStaff($admin, 'POST', "assessment-versions/{$draft['version']}/items", ['dimension_id' => $dimensionR, 'stem' => 'Saya suka bekerja dengan alat']))->json('data.id');

    // Butir dari dimensi versi lain ditolak.
    $other = talentDraft($w, code: 'LAIN');
    $foreignDimension = pmbOk($w->asStaff($admin, 'POST', "assessment-versions/{$other['version']}/dimensions", ['code' => 'R', 'name' => 'R lain']))->json('data.id');
    $crossed = $w->asStaff($admin, 'POST', "assessment-versions/{$draft['version']}/items", ['dimension_id' => $foreignDimension, 'stem' => 'Salah tempat']);
    expect($crossed->status())->toBe(422)->and($crossed->json('errors.code'))->toBe('DIMENSION_NOT_IN_VERSION');

    talentAddItems($w, $draft['version']);

    // Belum disetujui: publikasi ditolak walaupun instrumen lengkap.
    $early = $w->asStaff('admission_approver', 'POST', "assessment-versions/{$draft['version']}/publish");
    expect($early->status())->toBe(409)->and($early->json('errors.code'))->toBe('VERSION_NOT_APPROVED');

    talentApprove($w, $draft['version']);
    pmbOk($w->asStaff('admission_approver', 'POST', "assessment-versions/{$draft['version']}/publish"));

    $locked = $w->asStaff($admin, 'PATCH', "assessment-items/{$item}", ['stem' => 'Diubah setelah publikasi']);
    expect($locked->status())->toBe(409)->and($locked->json('errors.code'))->toBe('VERSION_LOCKED');

    $lockedDimension = $w->asStaff($admin, 'POST', "assessment-versions/{$draft['version']}/dimensions", ['code' => 'R', 'name' => 'Tambahan']);
    expect($lockedDimension->status())->toBe(409)->and($lockedDimension->json('errors.code'))->toBe('VERSION_LOCKED');

    // Revisi dibuat sebagai salinan. Versi terbit sebelumnya diarsipkan setelah versi baru terbit.
    $copy = pmbOk($w->asStaff($admin, 'POST', "assessment-versions/{$draft['version']}/clone", ['change_note' => 'Revisi butir']))->json('data.id');
    expect($w->asStaff($admin, 'GET', "assessment-versions/{$copy}")->json('data.status'))->toBe('draft');
    talentApprove($w, $copy);
    pmbOk($w->asStaff('admission_approver', 'POST', "assessment-versions/{$copy}/publish"));

    expect($w->asStaff($admin, 'GET', "assessment-versions/{$draft['version']}")->json('data.status'))->toBe('archived');
    expect($w->asStaff($admin, 'GET', "assessment-versions/{$copy}")->json('data.status'))->toBe('published');
});

it('menolak publikasi versi untuk seleksi sebelum validasi dan persetujuan fakultas', function (): void {
    $w = PmbWorld::create($this);
    $draft = talentDraft($w, purpose: 'admissions');
    talentFillInstrument($w, $draft['version']);

    $response = $w->asStaff('admission_administrator', 'POST', "assessment-versions/{$draft['version']}/submit");

    expect($response->status())->toBe(422)->and($response->json('errors.code'))->toBe('ADMISSIONS_NOT_APPROVED');
});

it('menutup sesi saat waktu habis: jawaban parsial tidak dinilai, jawaban lengkap tetap dinilai', function (): void {
    $w = PmbWorld::create($this);
    $cfg = pmbConfigure($w);
    $instrument = talentPublished($w, durationMinutes: 10);

    // Peserta pertama menjawab sebagian lalu waktunya habis.
    $partial = talentApplicant($w, $cfg, 'parsial@example.test');
    pmbOk($w->asApplicant($partial['token'], 'POST', "applications/{$partial['application']}/assessments/consent"));
    $partialSession = talentStart($w, $partial['token'], $partial['application'], $instrument['version']);
    pmbOk($w->asApplicant($partial['token'], 'PUT', "assessment-sessions/{$partialSession['id']}/responses", [
        'responses' => array_slice(talentAnswers($instrument['items']), 0, 3),
    ]));

    Carbon::setTestNow(now()->addMinutes(11));

    $late = $w->asApplicant($partial['token'], 'PUT', "assessment-sessions/{$partialSession['id']}/responses", [
        'responses' => [['item_id' => $instrument['items'][5]['id'], 'value' => 3]],
    ]);
    expect($late->status())->toBe(409)->and($late->json('errors.code'))->toBe('SESSION_EXPIRED');

    $closed = AssessmentSession::query()->withoutGlobalScopes()->findOrFail($partialSession['id']);
    expect($closed->status)->toBe(AssessmentSessionStatus::Expired)
        ->and(AssessmentScore::query()->withoutGlobalScopes()->where('session_id', $closed->getKey())->count())->toBe(0)
        ->and($closed->holland_code)->toBeNull();

    // Peserta kedua sudah menjawab lengkap sebelum waktunya habis, tetapi mengirim setelahnya.
    Carbon::setTestNow();
    $full = talentApplicant($w, $cfg, 'lengkap@example.test');
    pmbOk($w->asApplicant($full['token'], 'POST', "applications/{$full['application']}/assessments/consent"));
    $fullSession = talentStart($w, $full['token'], $full['application'], $instrument['version']);
    pmbOk($w->asApplicant($full['token'], 'PUT', "assessment-sessions/{$fullSession['id']}/responses", ['responses' => talentAnswers($instrument['items'])]));

    Carbon::setTestNow(now()->addMinutes(11));

    $submitted = pmbOk($w->asApplicant($full['token'], 'POST', "assessment-sessions/{$fullSession['id']}/submit"))->json('data');
    expect($submitted['status'])->toBe('expired');

    pmbOk($w->asStaff('admission_approver', 'POST', "assessment-sessions/{$fullSession['id']}/publish"));

    $result = pmbOk($w->asApplicant($full['token'], 'GET', "assessment-sessions/{$fullSession['id']}/result"))->json('data');
    expect($result['holland_code'])->toBe('RIA');
});

it('membatasi akses sesi pada pemiliknya dan menolak pendaftaran yang belum dikirim', function (): void {
    $w = PmbWorld::create($this);
    $cfg = pmbConfigure($w);
    $instrument = talentPublished($w);

    $owner = talentApplicant($w, $cfg, 'pemilik@example.test');
    pmbOk($w->asApplicant($owner['token'], 'POST', "applications/{$owner['application']}/assessments/consent"));
    $session = talentStart($w, $owner['token'], $owner['application'], $instrument['version']);

    $otherToken = pmbApplicantToken($w, 'lain@example.test', 'Citra Lestari');
    expect($w->asApplicant($otherToken, 'GET', "assessment-sessions/{$session['id']}")->status())->toBe(404);
    expect($w->asApplicant($otherToken, 'GET', "applications/{$owner['application']}/assessments")->status())->toBe(404);

    // Pendaftaran draft belum dikirim: asesmen tidak bisa dimulai.
    $draftToken = pmbApplicantToken($w, 'draf@example.test', 'Dewi Sartika');
    $draft = pmbOk($w->asApplicant($draftToken, 'POST', 'applications', ['wave_path_id' => $cfg['wavePath']], ['Idempotency-Key' => (string) Str::uuid()]))->json('data.id');
    pmbOk($w->asApplicant($draftToken, 'POST', "applications/{$draft}/assessments/consent"));

    $catalog = pmbOk($w->asApplicant($draftToken, 'GET', "applications/{$draft}/assessments"))->json('data.assessments');
    expect($catalog[0]['blocked_reason'])->toBe('APPLICATION_NOT_SUBMITTED');

    $start = $w->asApplicant($draftToken, 'POST', "applications/{$draft}/assessments/{$instrument['version']}/sessions");
    expect($start->status())->toBe(422)->and($start->json('errors.code'))->toBe('APPLICATION_NOT_SUBMITTED');
});

it('tidak mengizinkan staf tanpa izin asesmen untuk mengubah konfigurasi', function (): void {
    $w = PmbWorld::create($this);

    $response = $w->asStaff('admission_verifier', 'POST', 'assessment-types', ['code' => 'MINAT', 'name' => 'Minat', 'domain' => 'interest']);

    expect($response->status())->toBe(403);
});
