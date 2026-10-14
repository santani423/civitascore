<?php

require_once __DIR__.'/../Support/helpers.php';

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Admission\Models\Application;
use Modules\Admission\Models\Broadcast;
use Modules\Admission\Models\PaymentTransaction;
use Modules\Admission\Models\Refund;
use Modules\Admission\Models\SelectionScore;
use Modules\Admission\Tests\Support\PmbWorld;

beforeEach(function (): void {
    Storage::fake(config('pmb.storage_disk', 'local'));
    config(['pmb.captcha.driver' => 'none']);
});

it('runs the document revision loop until verification', function (): void {
    $w = PmbWorld::create($this);
    $cfg = pmbConfigure($w);
    $token = pmbApplicantToken($w, 'rev@example.test', 'Rina Kartika');
    $app = pmbSubmitApplication($w, $token, $cfg['wavePath'], 82);
    pmbPayRegistration($w, $token, $app);

    // Petugas meminta perbaikan foto; pendaftar melihat catatan & batas waktu.
    pmbOk($w->asStaff('admission_verifier', 'POST', "applications/{$app}/claim"));
    $lock = pmbOk($w->asStaff('admission_verifier', 'GET', "applications/{$app}"))->json('data.lock_version');
    pmbOk($w->asStaff('admission_verifier', 'POST', "applications/{$app}/request-revision", [
        'targets' => ['documents' => ['PHOTO']], 'note_for_applicant' => 'Unggah ulang pas foto dengan latar polos.', 'lock_version' => $lock,
    ]));

    $notes = pmbOk($w->asApplicant($token, 'GET', "applications/{$app}/notes"))->json('data.notes');
    expect($notes[0]['body'])->toContain('latar polos');

    $photo = collect(pmbOk($w->asApplicant($token, 'GET', "applications/{$app}/documents"))->json('data'))->firstWhere('document_type.code', 'PHOTO');
    expect($photo['status'])->toBe('revision_requested');
    pmbOk($w->asApplicant($token, 'POST', "applications/{$app}/documents/{$photo['id']}/versions", ['file' => UploadedFile::fake()->image('foto2.jpg', 450, 600)]));

    $lock = pmbOk($w->asApplicant($token, 'GET', "applications/{$app}"))->json('data.lock_version');
    pmbOk($w->asApplicant($token, 'POST', "applications/{$app}/submit", ['declarations' => [], 'lock_version' => $lock], ['Idempotency-Key' => (string) Str::uuid()]));

    // Versi lama tetap tersimpan (riwayat), versi baru ditinjau lalu aplikasi diverifikasi.
    pmbOk($w->asStaff('admission_verifier', 'POST', "applications/{$app}/claim"));
    $docs = pmbOk($w->asStaff('admission_verifier', 'GET', "applications/{$app}/documents"))->json('data.documents');
    $photoDoc = collect($docs)->firstWhere('type_code', 'PHOTO');
    expect($photoDoc['versions'])->toHaveCount(2);
    foreach ($docs as $doc) {
        if ($doc['status'] !== 'approved') {
            pmbOk($w->asStaff('admission_verifier', 'POST', "application-documents/{$doc['id']}/reviews", ['decision' => 'approved', 'version_id' => $doc['current_version_id']]));
        }
    }
    $lock = pmbOk($w->asStaff('admission_verifier', 'GET', "applications/{$app}"))->json('data.lock_version');
    pmbOk($w->asStaff('admission_verifier', 'POST', "applications/{$app}/verify", ['lock_version' => $lock]));

    expect(Application::query()->withoutGlobalScopes()->find($app)->verification_status->value)->toBe('verified');
    $timeline = pmbOk($w->asStaff('admission_administrator', 'GET', "applications/{$app}/timeline"))->json('data');
    expect(collect($timeline)->pluck('to'))->toContain('revision_requested', 'verified');
});

it('schedules a CBT exam, issues cards, and records the computed score', function (): void {
    $w = PmbWorld::create($this);
    $cfg = pmbConfigure($w, withCbt: true);
    $token = pmbApplicantToken($w, 'cbt@example.test', 'Satria Wibowo');
    $app = pmbSubmitApplication($w, $token, $cfg['wavePath'], 80);
    pmbPayAndVerify($w, $token, $app);

    $component = collect(pmbOk($w->asStaff('admission_selection_committee', 'GET', "selection-schemes/{$cfg['scheme']}"))->json('data.components'))->firstWhere('code', 'TPA');
    $venue = pmbOk($w->asStaff('admission_administrator', 'POST', 'exam-venues', ['name' => 'Gedung A', 'address' => 'Kampus Utama']))->json('data.id');
    $room = pmbOk($w->asStaff('admission_administrator', 'POST', "exam-venues/{$venue}/rooms", ['code' => 'A101', 'name' => 'Lab Komputer 1', 'capacity' => 30]))->json('data.id');
    $schedule = pmbOk($w->asStaff('admission_administrator', 'POST', "waves/{$cfg['wave']}/exam-schedules", [
        'selection_component_id' => $component['id'], 'wave_path_ids' => [$cfg['wavePath']], 'mode' => 'cbt', 'title' => 'TPA Gelombang 1',
        'exam_date' => now()->toDateString(), 'cbt_duration_minutes' => 60,
    ]))->json('data.id');
    $session = pmbOk($w->asStaff('admission_administrator', 'POST', "exam-schedules/{$schedule}/sessions", [
        'room_id' => $room, 'code' => 'S1', 'starts_at' => now()->subMinutes(5)->toIso8601String(), 'ends_at' => now()->addHours(2)->toIso8601String(), 'capacity' => 30,
    ]))->json('data.id');

    foreach ([['A', 'B'], ['C', 'A'], ['D', 'D']] as $i => [$correct, $_]) {
        pmbOk($w->asStaff('admission_administrator', 'POST', "exam-schedules/{$schedule}/cbt-questions", [
            'question' => 'Soal '.($i + 1), 'correct_option' => $correct, 'points' => 1, 'sort_order' => $i,
            'options' => [['key' => 'A', 'text' => 'a'], ['key' => 'B', 'text' => 'b'], ['key' => 'C', 'text' => 'c'], ['key' => 'D', 'text' => 'd']],
        ]));
    }

    expect(pmbOk($w->asStaff('admission_administrator', 'POST', "exam-schedules/{$schedule}/auto-assign", ['strategy' => 'registration_number']))->json('data.assigned'))->toBe(1);
    pmbOk($w->asStaff('admission_administrator', 'POST', "exam-schedules/{$schedule}/publish"));

    $exams = pmbOk($w->asApplicant($token, 'GET', "applications/{$app}/exams"))->json('data');
    $participant = $exams['participations'][0];
    expect($participant['card_available'])->toBeTrue()->and($participant['participant_number'])->toStartWith('PMB27');
    expect(pmbOk($w->asApplicant($token, 'GET', "exam-participants/{$participant['id']}/card"))->json('data.url'))->toContain('/pmb/files/');

    // Kerjakan CBT: 2 dari 3 benar → nilai 66.67 pada skala 0–100.
    $attempt = pmbOk($w->asApplicant($token, 'POST', "exam-participants/{$participant['id']}/cbt/start"))->json('data');
    expect($attempt['questions'])->toHaveCount(3);
    $answers = ['Soal 1' => 'A', 'Soal 2' => 'C', 'Soal 3' => 'A'];

    foreach ($attempt['questions'] as $question) {
        pmbOk($w->asApplicant($token, 'PUT', "exam-participants/{$participant['id']}/cbt/answers", ['question_id' => $question['id'], 'option' => $answers[$question['question']]]));
    }

    pmbOk($w->asApplicant($token, 'POST', "exam-participants/{$participant['id']}/cbt/submit"));
    $w->asApplicant($token, 'PUT', "exam-participants/{$participant['id']}/cbt/answers", ['question_id' => $attempt['questions'][0]['id'], 'option' => 'B'])->assertStatus(409);

    $w->tenant();
    $score = SelectionScore::query()->where('application_id', $app)->where('selection_component_id', $component['id'])->firstOrFail();
    expect(round((float) $score->raw_score, 2))->toBe(66.67);

    // Pengawas: presensi sesi menampilkan peserta yang hadir.
    $sheet = pmbOk($w->asStaff('admission_administrator', 'GET', "exam-sessions/{$session}"))->json('data.participants');
    expect($sheet[0]['attendance_status'])->toBe('present');
});

it('requires a second finance officer to approve a refund before it is completed', function (): void {
    $w = PmbWorld::create($this);
    $cfg = pmbConfigure($w);
    $token = pmbApplicantToken($w, 'refund@example.test', 'Rudi Hartono');
    $app = pmbSubmitApplication($w, $token, $cfg['wavePath'], 70);
    $invoice = pmbOk($w->asApplicant($token, 'GET', "applications/{$app}/invoices"))->json('data.0');
    pmbOk($w->asStaff('finance_administrator', 'POST', "invoices/{$invoice['id']}/cashier-payments", ['amount' => 150000, 'receipt_number' => 'KW-77', 'paid_at' => now()->toIso8601String()], ['Idempotency-Key' => (string) Str::uuid()]));
    $transaction = PaymentTransaction::query()->withoutGlobalScopes()->where('invoice_id', $invoice['id'])->where('status', 'success')->firstOrFail();

    $refundId = pmbOk($w->asStaff('finance_administrator', 'POST', 'refunds', [
        'transaction_id' => $transaction->getKey(), 'amount' => 150000, 'reason_code' => 'WITHDRAWAL', 'reason_note' => 'Pendaftar mengundurkan diri sebelum seleksi',
        'beneficiary_bank' => 'BNI', 'beneficiary_account' => '0123456789', 'beneficiary_name' => 'Rudi Hartono',
    ], ['Idempotency-Key' => (string) Str::uuid()]))->json('data.id');

    $refund = Refund::query()->withoutGlobalScopes()->findOrFail($refundId);
    expect($refund->status->value)->toBe('pending_approval');
    // Data rekening tersimpan terenkripsi dan disamarkan di respons.
    expect(pmbOk($w->asStaff('finance_administrator', 'GET', 'refunds'))->json('data.0.beneficiary_account_masked'))->not->toContain('0123456');

    pmbApproveCurrentStep($w, $this, $refund->approval_request_id, $w->staff['finance_administrator'])->assertStatus(409);
    $head = $w->staffWithRole('finance_administrator');
    pmbOk(pmbApproveCurrentStep($w, $this, $refund->approval_request_id, $head));

    expect($refund->refresh()->status->value)->toBeIn(['approved', 'processing']);
    pmbOk($w->asStaff('finance_administrator', 'POST', "refunds/{$refundId}/complete", ['proof' => UploadedFile::fake()->image('transfer.jpg'), 'completed_at' => now()->toIso8601String()]));
    expect($refund->refresh()->status->value)->toBe('completed');
    expect(pmbOk($w->asStaff('finance_administrator', 'GET', "invoices/{$invoice['id']}"))->json('data.status'))->toBe('refunded');
});

it('serves dashboards, reports, and audited exports', function (): void {
    $w = PmbWorld::create($this);
    $cfg = pmbConfigure($w);
    $token = pmbApplicantToken($w, 'rep@example.test', 'Lina Marlina');
    pmbSubmitApplication($w, $token, $cfg['wavePath'], 77);

    $summary = pmbOk($w->asStaff('admission_administrator', 'GET', 'dashboard/summary', ['period_id' => $cfg['period']]))->json('data');
    expect($summary['kpi_04_submitted'])->toBe(1)->and($summary['funnel'][1]['value'])->toBe(1);

    $report = pmbOk($w->asStaff('admission_administrator', 'GET', 'reports/applicants', ['period_id' => $cfg['period']]))->json('data');
    expect($report['rows'])->toHaveCount(1);

    // Ekspor wajib beralasan, tercatat, dan hanya dapat diunduh pemiliknya.
    $w->asStaff('admission_administrator', 'POST', 'reports/applicants/exports', ['format' => 'xlsx'])->assertStatus(422);
    $export = pmbOk($w->asStaff('admission_administrator', 'POST', 'reports/applicants/exports', ['format' => 'xlsx', 'reason' => 'Rapat evaluasi panitia', 'filters' => ['period_id' => $cfg['period']]]))->json('data');
    $ready = pmbOk($w->asStaff('admission_administrator', 'GET', "exports/{$export['id']}"))->json('data');
    expect($ready['status'])->toBe('ready')->and($ready['url'])->toContain('/pmb/files/')->and($ready['row_count'])->toBe(1);
    $w->asStaff('admission_registrar', 'GET', "exports/{$export['id']}")->assertStatus(404);

    $logs = pmbOk($w->asStaff('admission_administrator', 'GET', 'export-logs'))->json('data');
    expect($logs[0]['reason'])->toBe('Rapat evaluasi panitia');
});

it('supports applicant chat and targeted broadcasts', function (): void {
    $w = PmbWorld::create($this);
    $cfg = pmbConfigure($w);
    $token = pmbApplicantToken($w, 'chat@example.test', 'Maya Sari');
    pmbSubmitApplication($w, $token, $cfg['wavePath'], 79);

    $thread = pmbOk($w->asApplicant($token, 'POST', 'chat-threads', ['subject' => 'Jadwal ujian', 'message' => 'Kapan jadwal ujian diumumkan?']))->json('data.id');
    $adminThreads = pmbOk($w->asStaff('admission_verifier', 'GET', 'chat-threads'))->json('data');
    expect($adminThreads[0]['unread'])->toBe(1);
    pmbOk($w->asStaff('admission_verifier', 'POST', "chat-threads/{$thread}/messages", ['body' => 'Jadwal diumumkan setelah verifikasi selesai.']));

    $messages = pmbOk($w->asApplicant($token, 'GET', "chat-threads/{$thread}/messages"))->json('data.messages');
    // Pesan sistem (peringatan keamanan) + pertanyaan pendaftar + balasan staf.
    expect(collect($messages)->pluck('sender_type')->all())->toBe(['system', 'applicant', 'user']);

    $broadcast = pmbOk($w->asStaff('admission_administrator', 'POST', 'broadcasts', [
        'title' => 'Info verifikasi', 'channel' => 'email', 'content_subject' => 'Info PMB', 'content_body' => 'Verifikasi berkas sedang berlangsung.',
        'audience_filter' => ['period_id' => $cfg['period'], 'form_status' => ['submitted']],
    ]))->json('data.id');
    expect(pmbOk($w->asStaff('admission_administrator', 'POST', "broadcasts/{$broadcast}/preview-audience"))->json('data.count'))->toBe(1);
    pmbOk($w->asStaff('admission_administrator', 'POST', "broadcasts/{$broadcast}/schedule"));

    expect(Broadcast::query()->withoutGlobalScopes()->find($broadcast)->status->value)->toBe('sent');

    // Broadcast berisi NIK ditolak (13 §A.5).
    $w->asStaff('admission_administrator', 'POST', 'broadcasts', ['title' => 'X', 'channel' => 'email', 'content_body' => 'NIK 3171010101050001'])->assertStatus(422);
});
