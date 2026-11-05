<?php

require_once __DIR__.'/../Support/helpers.php';
require_once __DIR__.'/../Support/assessment_helpers.php';

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Modules\Admission\Models\Application;
use Modules\Admission\Models\AssessmentSession;
use Modules\Admission\Models\AssessmentSessionEvent;
use Modules\Admission\Tests\Support\PmbWorld;

beforeEach(function (): void {
    config(['pmb.captcha.driver' => 'none']);
});

afterEach(function (): void {
    Carbon::setTestNow();
});

/** Peserta yang sudah menyetujui dan memulai sesi; `$answers` = jumlah butir yang dijawab (null = semua). */
function talentParticipant(PmbWorld $w, array $cfg, array $instrument, string $email, ?int $answers, bool $submit = false, float $score = 85): array
{
    $peserta = talentApplicant($w, $cfg, $email, $score);
    pmbOk($w->asApplicant($peserta['token'], 'POST', "applications/{$peserta['application']}/assessments/consent"));
    $session = talentStart($w, $peserta['token'], $peserta['application'], $instrument['version']);
    $responses = talentAnswers($instrument['items']);

    if ($answers !== null) {
        $responses = array_slice($responses, 0, $answers);
    }

    if ($responses !== []) {
        pmbOk($w->asApplicant($peserta['token'], 'PUT', "assessment-sessions/{$session['id']}/responses", ['responses' => $responses]));
    }

    if ($submit) {
        pmbOk($w->asApplicant($peserta['token'], 'POST', "assessment-sessions/{$session['id']}/submit"));
    }

    return [...$peserta, 'session' => $session['id']];
}

it('daftar peserta dapat difilter dan jawaban mentah hanya tampil untuk yang berwenang', function (): void {
    $w = PmbWorld::create($this);
    $cfg = pmbConfigure($w);
    $instrument = talentPublished($w);

    $selesai = talentParticipant($w, $cfg, $instrument, 'selesai@example.test', null, submit: true);
    $berjalan = talentParticipant($w, $cfg, $instrument, 'berjalan@example.test', 3, score: 80);

    $all = pmbOk($w->asStaff('admission_administrator', 'GET', 'assessment-sessions'))->json('data');
    expect($all)->toHaveCount(2);

    $submitted = pmbOk($w->asStaff('admission_administrator', 'GET', 'assessment-sessions', ['status' => 'submitted']))->json('data');
    expect($submitted)->toHaveCount(1)->and($submitted[0]['status'])->toBe('submitted');

    expect(pmbOk($w->asStaff('admission_administrator', 'GET', 'assessment-sessions', ['period_id' => $cfg['period']]))->json('data'))->toHaveCount(2);
    expect(pmbOk($w->asStaff('admission_administrator', 'GET', 'assessment-sessions', ['study_program_id' => $w->program->getKey()]))->json('data'))->toHaveCount(2);
    expect(pmbOk($w->asStaff('admission_administrator', 'GET', 'assessment-sessions', ['study_program_id' => $w->otherProgram->getKey()]))->json('data'))->toBeEmpty();

    $nomor = Application::query()->withoutGlobalScopes()->find($berjalan['application'])->registration_number;
    $cari = pmbOk($w->asStaff('admission_administrator', 'GET', 'assessment-sessions', ['search' => $nomor]))->json('data');
    expect($cari)->toHaveCount(1)->and($cari[0]['applicant_name'])->toBe('Budi Santoso');

    // Jawaban mentah hanya untuk role yang punya pmb_sensitive.read; approver tidak.
    $admin = pmbOk($w->asStaff('admission_administrator', 'GET', "assessment-sessions/{$selesai['session']}"))->json('data');
    expect($admin['responses'])->toHaveCount(12)->and($admin['responses_restricted'])->toBeFalse();

    $approver = pmbOk($w->asStaff('admission_approver', 'GET', "assessment-sessions/{$selesai['session']}"))->json('data');
    expect($approver['responses'])->toBeNull()
        ->and($approver['responses_restricted'])->toBeTrue()
        ->and($approver['events'])->not->toBeEmpty();
});

it('tanda perlu review menahan publikasi hingga review selesai', function (): void {
    $w = PmbWorld::create($this);
    $cfg = pmbConfigure($w);
    $instrument = talentPublished($w);
    $peserta = talentParticipant($w, $cfg, $instrument, 'review@example.test', null, submit: true);
    $s = $peserta['session'];

    pmbOk($w->asStaff('admission_administrator', 'POST', "assessment-sessions/{$s}/flag", ['reason' => 'Jawaban selesai dalam waktu sangat singkat']));

    $blocked = $w->asStaff('admission_approver', 'POST', "assessment-sessions/{$s}/publish");
    expect($blocked->status())->toBe(409)->and($blocked->json('errors.code'))->toBe('REVIEW_REQUIRED');

    pmbOk($w->asStaff('admission_administrator', 'POST', "assessment-sessions/{$s}/review-complete", ['note' => 'Pola jawaban wajar']));
    pmbOk($w->asStaff('admission_approver', 'POST', "assessment-sessions/{$s}/publish"));

    $review = pmbOk($w->asStaff('admission_administrator', 'GET', "assessment-sessions/{$s}/review"))->json('data');
    expect($review['status'])->toBe('published')
        ->and($review['review_status'])->toBe('reviewed')
        ->and($review['publication_history'])->toHaveCount(1)
        ->and($review['publication_history'][0]['payload']['version_id'])->toBe($instrument['version'])
        ->and($review['publication_history'][0]['payload']['scoring_engine'])->toBe('riasec-mean-v1')
        ->and(collect($review['review_notes'])->pluck('kind')->all())->toBe(['flag', 'review_completed']);
});

it('invalidasi memerlukan alasan, menghentikan hasil, dan sesi yang sudah terbit tidak dapat diinvalidasi', function (): void {
    $w = PmbWorld::create($this);
    $cfg = pmbConfigure($w);
    $instrument = talentPublished($w);
    $a = talentParticipant($w, $cfg, $instrument, 'invalid-a@example.test', null, submit: true);
    $b = talentParticipant($w, $cfg, $instrument, 'invalid-b@example.test', null, submit: true, score: 70);

    $pendek = $w->asStaff('admission_administrator', 'POST', "assessment-sessions/{$a['session']}/invalidate", ['reason' => 'kurang']);
    expect($pendek->status())->toBe(422);

    pmbOk($w->asStaff('admission_administrator', 'POST', "assessment-sessions/{$a['session']}/invalidate", ['reason' => 'Peserta terdeteksi memakai dua perangkat saat ujian']));

    $row = AssessmentSession::query()->withoutGlobalScopes()->find($a['session']);
    expect($row->status->value)->toBe('invalidated')->and($row->invalidation_reason)->toContain('dua perangkat');

    $hasil = $w->asApplicant($a['token'], 'GET', "assessment-sessions/{$a['session']}/result");
    expect($hasil->status())->toBe(409)->and($hasil->json('errors.code'))->toBe('RESULT_NOT_PUBLISHED');

    $terbit = $w->asStaff('admission_approver', 'POST', "assessment-sessions/{$a['session']}/publish");
    expect($terbit->status())->toBe(422)->and($terbit->json('errors.code'))->toBe('INVALID_TRANSITION');

    pmbOk($w->asStaff('admission_approver', 'POST', "assessment-sessions/{$b['session']}/publish"));
    $sudahTerbit = $w->asStaff('admission_administrator', 'POST', "assessment-sessions/{$b['session']}/invalidate", ['reason' => 'Ingin dibatalkan setelah terbit']);
    expect($sudahTerbit->status())->toBe(422)->and($sudahTerbit->json('errors.code'))->toBe('INVALID_TRANSITION');

    $log = pmbOk($w->asStaff('admission_approver', 'GET', 'assessment-audit-logs', ['auditable_id' => $a['session']]))->json('data');
    expect(collect($log)->pluck('reason')->filter()->implode(' '))->toContain('dua perangkat');
});

it('dashboard menghitung status dan menahan distribusi hasil yang belum cukup besar', function (): void {
    $w = PmbWorld::create($this);
    $cfg = pmbConfigure($w);
    $instrument = talentPublished($w);

    talentParticipant($w, $cfg, $instrument, 'dash-a@example.test', null, submit: true);
    talentParticipant($w, $cfg, $instrument, 'dash-b@example.test', 3);
    talentParticipant($w, $cfg, $instrument, 'dash-d@example.test', null, submit: true, score: 70);

    // Peserta C: pendaftaran terkirim tetapi belum pernah memulai asesmen.
    $c = talentApplicant($w, $cfg, 'dash-c@example.test', 75);

    $a = AssessmentSession::query()->withoutGlobalScopes()->get()->first(fn ($s) => $s->status->value === 'submitted');
    pmbOk($w->asStaff('admission_approver', 'POST', "assessment-sessions/{$a->getKey()}/publish"));

    $dashboard = pmbOk($w->asStaff('admission_administrator', 'GET', 'assessment-dashboard'))->json('data');

    expect($dashboard['totals']['participants'])->toBe(3)
        ->and($dashboard['totals']['in_progress'])->toBe(1)
        ->and($dashboard['totals']['submitted'])->toBe(1)
        ->and($dashboard['totals']['published'])->toBe(1)
        ->and($dashboard['totals']['not_started'])->toBe(1)
        ->and($dashboard['totals']['needs_review'])->toBe(0)
        ->and($dashboard['status_distribution']['in_progress'])->toBe(1)
        ->and($dashboard['status_distribution']['expired'])->toBe(0)
        ->and($dashboard['completion_trend'])->toHaveCount(30)
        ->and(collect($dashboard['completion_trend'])->last()['submitted'])->toBe(2)
        ->and($dashboard['participants_by_period'][0]['participants'])->toBe(3)
        ->and($dashboard['result_distribution']['suppressed'])->toBeTrue()
        ->and($dashboard['result_distribution']['items'])->toBeEmpty();

    // Tanpa izin asesmen, dashboard tidak dapat dibuka.
    expect($w->asStaff('admission_verifier', 'GET', 'assessment-dashboard')->status())->toBe(403);
    expect($c['application'])->not->toBeEmpty();
});

it('pmb:tick menutup sesi tepat pada batas waktu, sekali saja, dan tidak menyentuh sesi final', function (): void {
    $w = PmbWorld::create($this);
    $cfg = pmbConfigure($w);
    $instrument = talentPublished($w, durationMinutes: 10);

    $parsial = talentParticipant($w, $cfg, $instrument, 'tick-parsial@example.test', 3);
    $lengkap = talentParticipant($w, $cfg, $instrument, 'tick-lengkap@example.test', null, score: 80);
    $selesai = talentParticipant($w, $cfg, $instrument, 'tick-selesai@example.test', null, submit: true, score: 75);

    // Batas waktu tiap sesi ditentukan saat mulai, jadi tidak sama persis. Uji pada batas paling awal dan paling akhir.
    $deadlines = collect([$parsial, $lengkap])->map(fn (array $p) => AssessmentSession::query()->withoutGlobalScopes()->find($p['session'])->deadline_at);
    $earliest = $deadlines->min();
    $latest = $deadlines->max();

    // Satu detik sebelum batas waktu paling awal: belum boleh ditutup.
    Carbon::setTestNow($earliest->copy()->subSecond());
    Artisan::call('pmb:tick');
    expect(AssessmentSession::query()->withoutGlobalScopes()->find($parsial['session'])->status->value)->toBe('in_progress');

    // Tepat pada batas waktu terakhir: semua sesi jatuh tempo. Jawaban parsial tanpa skor; jawaban lengkap dinilai.
    Carbon::setTestNow($latest);
    Artisan::call('pmb:tick');

    $parsialRow = AssessmentSession::query()->withoutGlobalScopes()->find($parsial['session']);
    $lengkapRow = AssessmentSession::query()->withoutGlobalScopes()->find($lengkap['session']);
    $selesaiRow = AssessmentSession::query()->withoutGlobalScopes()->find($selesai['session']);

    expect($parsialRow->status->value)->toBe('expired')->and($parsialRow->holland_code)->toBeNull()
        ->and($lengkapRow->status->value)->toBe('expired')->and($lengkapRow->holland_code)->toBe('RIA')
        ->and($selesaiRow->status->value)->toBe('submitted');

    // Dijalankan ulang: tidak ada perubahan dan tidak ada event ganda.
    $events = fn () => AssessmentSessionEvent::query()->withoutGlobalScopes()->where('event_type', 'expired')->count();
    $before = $events();
    Artisan::call('pmb:tick');
    expect($events())->toBe($before)->and($before)->toBe(2);
});

it('token pendaftar tidak dapat membuka rute admin dan staf tidak dapat membuka rute peserta', function (): void {
    $w = PmbWorld::create($this);
    $cfg = pmbConfigure($w);
    $instrument = talentPublished($w);
    $peserta = talentParticipant($w, $cfg, $instrument, 'batas-peran@example.test', null, submit: true);

    // Token pendaftar pada rute admin: ditolak oleh guard staf.
    $admin = $this->withHeaders(['Authorization' => 'Bearer '.$peserta['token']])->getJson('/api/v1/pmb/admin/assessment-dashboard');
    expect($admin->status())->toBeIn([401, 403]);

    $sessions = $this->withHeaders(['Authorization' => 'Bearer '.$peserta['token']])->getJson('/api/v1/pmb/admin/assessment-sessions');
    expect($sessions->status())->toBeIn([401, 403]);

    // Akun staf tidak dapat membuka rute peserta: guard pendaftar menolak akun yang bukan pendaftar.
    $staffOnApplicant = $this->actingAs($w->staff['admission_administrator'])->getJson("/api/v1/pmb/applicant/assessment-sessions/{$peserta['session']}");
    expect($staffOnApplicant->status())->toBeIn([401, 403, 404]);
});
