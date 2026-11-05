<?php

require_once __DIR__.'/../Support/helpers.php';
require_once __DIR__.'/../Support/assessment_helpers.php';

use Illuminate\Support\Carbon;
use Modules\Admission\Tests\Support\PmbWorld;

beforeEach(function (): void {
    config(['pmb.captcha.driver' => 'none']);
});

afterEach(function (): void {
    Carbon::setTestNow();
});

it('menjalankan persetujuan dua pihak dengan pemisahan tugas dan menandai butir sebagai disetujui', function (): void {
    $w = PmbWorld::create($this);
    $draft = talentDraft($w);
    talentFillInstrument($w, $draft['version']);
    $v = $draft['version'];

    pmbOk($w->asStaff('admission_administrator', 'POST', "assessment-versions/{$v}/submit", ['note' => 'Siap ditelaah']));

    // Penyusun tidak boleh menyetujui pengajuannya sendiri.
    expect($w->asStaff('admission_administrator', 'POST', "assessment-versions/{$v}/decision", ['decision' => 'approve'])->status())->toBe(409);

    $reviewer = $w->staffWithRole('admission_administrator');
    pmbOk($w->asStaff($reviewer, 'POST', "assessment-versions/{$v}/decision", ['decision' => 'approve']));

    $between = pmbOk($w->asStaff('admission_administrator', 'GET', "assessment-versions/{$v}"))->json('data');
    expect($between['status'])->toBe('in_review')
        ->and($between['approval']['current_step'])->toBe('Pejabat Pengesah')
        ->and($between['approval']['can_decide'])->toBeTrue();

    pmbOk($w->asStaff('admission_approver', 'POST', "assessment-versions/{$v}/decision", ['decision' => 'approve']));

    $approved = pmbOk($w->asStaff('admission_approver', 'GET', "assessment-versions/{$v}"))->json('data');
    expect($approved['status'])->toBe('approved')->and($approved['approval']['status'])->toBe('approved');

    $approvedItems = pmbOk($w->asStaff('admission_administrator', 'GET', 'assessment-questions', ['version_id' => $v, 'status' => 'approved']))->json('data');
    expect($approvedItems)->toHaveCount(12);

    // Penerbitan hanya oleh pejabat pengesah.
    expect($w->asStaff('admission_administrator', 'POST', "assessment-versions/{$v}/publish")->status())->toBe(403);
    pmbOk($w->asStaff('admission_approver', 'POST', "assessment-versions/{$v}/publish"));

    expect($w->asStaff('admission_administrator', 'GET', "assessment-versions/{$v}")->json('data.status'))->toBe('published');
});

it('pengembalian kembali ke draft dan penolakan mengunci versi sesuai aturannya', function (): void {
    $w = PmbWorld::create($this);
    $draft = talentDraft($w);
    talentFillInstrument($w, $draft['version']);
    $v = $draft['version'];
    $reviewer = $w->staffWithRole('admission_administrator');

    pmbOk($w->asStaff('admission_administrator', 'POST', "assessment-versions/{$v}/submit"));

    $noComment = $w->asStaff($reviewer, 'POST', "assessment-versions/{$v}/decision", ['decision' => 'return']);
    expect($noComment->status())->toBe(422);

    pmbOk($w->asStaff($reviewer, 'POST', "assessment-versions/{$v}/decision", ['decision' => 'return', 'comment' => 'Perbaiki butir kedua.']));
    expect($w->asStaff('admission_administrator', 'GET', "assessment-versions/{$v}")->json('data.status'))->toBe('draft');

    // Dikembalikan berarti boleh diubah lagi.
    pmbOk($w->asStaff('admission_administrator', 'PATCH', "assessment-versions/{$v}", ['title' => 'Judul revisi']));

    pmbOk($w->asStaff('admission_administrator', 'POST', "assessment-versions/{$v}/submit"));
    pmbOk($w->asStaff($reviewer, 'POST', "assessment-versions/{$v}/decision", ['decision' => 'reject', 'comment' => 'Tidak sesuai kisi-kisi.']));

    expect($w->asStaff('admission_administrator', 'GET', "assessment-versions/{$v}")->json('data.status'))->toBe('rejected');

    // Versi yang ditolak tidak diedit; revisinya dibuat sebagai salinan.
    $locked = $w->asStaff('admission_administrator', 'PATCH', "assessment-versions/{$v}", ['title' => 'Tidak boleh']);
    expect($locked->status())->toBe(409)->and($locked->json('errors.code'))->toBe('VERSION_LOCKED');

    expect($w->asStaff('admission_administrator', 'POST', "assessment-types/{$draft['type']}/versions", ['title' => 'Baru', 'purpose' => 'exploratory'])->status())->toBe(201);
});

it('hanya satu versi terbuka per jenis, dan salinan membentuk draft baru yang dapat dibandingkan', function (): void {
    $w = PmbWorld::create($this);
    $instrument = talentPublished($w);
    $admin = 'admission_administrator';

    $copy = pmbOk($w->asStaff($admin, 'POST', "assessment-versions/{$instrument['version']}/clone", ['change_note' => 'Revisi']))->json('data');
    expect($copy['status'])->toBe('draft')->and($copy['version_no'])->toBe(2);

    $second = $w->asStaff($admin, 'POST', "assessment-types/{$instrument['type']}/versions", ['title' => 'Terlalu cepat', 'purpose' => 'exploratory']);
    expect($second->status())->toBe(409)->and($second->json('errors.code'))->toBe('VERSION_OPEN');

    $item = $w->asStaff($admin, 'GET', 'assessment-questions', ['version_id' => $copy['id'], 'search' => 'Butir R', 'sort' => 'code'])->json('data.0.id');
    pmbOk($w->asStaff($admin, 'PATCH', "assessment-items/{$item}", ['stem' => 'Saya suka merancang sistem']));
    $newItem = pmbOk($w->asStaff($admin, 'POST', "assessment-versions/{$copy['id']}/items", [
        'dimension_id' => $w->asStaff($admin, 'GET', "assessment-versions/{$copy['id']}")->json('data.dimensions.0.id'),
        'stem' => 'Butir tambahan untuk revisi',
    ]))->json('data.id');

    $diff = pmbOk($w->asStaff($admin, 'GET', "assessment-versions/{$instrument['version']}/diff/{$copy['id']}"))->json('data');
    expect($diff['items']['changed'])->toHaveCount(1)
        ->and($diff['items']['added'])->toHaveCount(1)
        ->and($diff['items']['added'][0]['stem'])->toBe('Butir tambahan untuk revisi')
        ->and($diff['items']['removed'])->toBeEmpty()
        ->and($diff['dimensions']['changed'])->toBeEmpty();

    // Versi terbit tidak berubah oleh revisi.
    expect(pmbOk($w->asStaff($admin, 'GET', 'assessment-questions', ['version_id' => $instrument['version'], 'status' => 'approved']))->json('data'))->toHaveCount(12);
    expect($newItem)->not->toBeEmpty();
});

it('pengarsipan memerlukan alasan, dan draft yang belum diajukan dapat dihapus', function (): void {
    $w = PmbWorld::create($this);
    $instrument = talentPublished($w);
    $admin = 'admission_administrator';

    // Pengarsipan memakai izin hapus (aksi `delete`), hanya dimiliki administrator penyusun.
    expect($w->asStaff('admission_approver', 'POST', "assessment-versions/{$instrument['version']}/archive", ['reason' => 'Diganti kisi-kisi 2028'])->status())->toBe(403);

    $noReason = $w->asStaff($admin, 'POST', "assessment-versions/{$instrument['version']}/archive", []);
    expect($noReason->status())->toBe(422);

    pmbOk($w->asStaff($admin, 'POST', "assessment-versions/{$instrument['version']}/archive", ['reason' => 'Diganti kisi-kisi 2028']));
    expect($w->asStaff($admin, 'GET', "assessment-versions/{$instrument['version']}")->json('data.status'))->toBe('archived');

    $other = talentDraft($w, code: 'LAIN');
    expect($w->asStaff($admin, 'DELETE', "assessment-versions/{$other['version']}")->status())->toBe(204);
    expect($w->asStaff($admin, 'GET', "assessment-versions/{$other['version']}")->status())->toBe(404);
});

it('bank soal menjaga kode unik, validasi, pencarian, pratinjau, dan penyalinan', function (): void {
    $w = PmbWorld::create($this);
    $draft = talentDraft($w);
    talentFillInstrument($w, $draft['version']);
    $admin = 'admission_administrator';
    $v = $draft['version'];

    $duplicate = $w->asStaff($admin, 'POST', "assessment-versions/{$v}/items", [
        'dimension_id' => $w->asStaff($admin, 'GET', "assessment-versions/{$v}")->json('data.dimensions.0.id'),
        'stem' => 'Kode sama dengan yang sudah ada', 'code' => 'Q-001',
    ]);
    expect($duplicate->status())->toBe(409)->and($duplicate->json('errors.code'))->toBe('CODE_DUPLICATE');

    $badCode = $w->asStaff($admin, 'POST', "assessment-versions/{$v}/items", [
        'dimension_id' => $w->asStaff($admin, 'GET', "assessment-versions/{$v}")->json('data.dimensions.0.id'),
        'stem' => 'Kode mengandung spasi', 'code' => 'kode salah',
    ]);
    expect($badCode->status())->toBe(422)->and($badCode->json('errors.code'))->toBe('INVALID_CODE');

    $reversed = pmbOk($w->asStaff($admin, 'GET', 'assessment-questions', ['version_id' => $v, 'search' => 'terbalik']))->json('data');
    expect($reversed)->toHaveCount(6);

    $dimensionR = pmbOk($w->asStaff($admin, 'GET', 'assessment-questions', ['version_id' => $v, 'dimension' => 'R']))->json('data');
    expect($dimensionR)->toHaveCount(2);

    $item = $dimensionR[0]['id'];
    $preview = pmbOk($w->asStaff($admin, 'GET', "assessment-items/{$item}/preview"))->json('data');
    expect($preview)->toHaveKeys(['id', 'stem', 'scale'])->not->toHaveKeys(['dimension', 'is_reversed', 'dimension_id']);

    // Salin butir dari versi terbit ke draft jenis lain ditolak; ke draft jenis yang sama diterima.
    $published = talentPublished($w, code: 'PUB');
    $typeOther = talentDraft($w, code: 'LAIN');
    $publishedItem = $published['items'][0]['id'];
    $mismatch = $w->asStaff($admin, 'POST', "assessment-items/{$publishedItem}/copy", ['version_id' => $typeOther['version']]);
    expect($mismatch->status())->toBe(422)->and($mismatch->json('errors.code'))->toBe('TYPE_MISMATCH');
});

it('peran tanpa izin asesmen tidak dapat melakukan aksi yang dibatasi', function (): void {
    $w = PmbWorld::create($this);
    $draft = talentDraft($w);

    // Pejabat pengesah menyetujui dan menerbitkan, tetapi tidak menyusun butir.
    $denied = $w->asStaff('admission_approver', 'POST', "assessment-versions/{$draft['version']}/items", ['dimension_id' => (string) str_repeat('0', 26), 'stem' => 'Tidak diizinkan']);
    expect($denied->status())->toBe(403);

    expect($w->asStaff('admission_verifier', 'GET', 'assessment-types')->status())->toBe(403);
    expect($w->asStaff('admission_verifier', 'GET', 'assessment-audit-logs')->status())->toBe(403);
});

it('audit mencatat pembuatan, persetujuan, dan penerbitan untuk versi', function (): void {
    $w = PmbWorld::create($this);
    $instrument = talentPublished($w);

    $logs = pmbOk($w->asStaff('admission_approver', 'GET', 'assessment-audit-logs', ['auditable_id' => $instrument['version']]))->json('data');
    $statuses = collect($logs)->map(fn (array $log) => $log['new_values']['status'] ?? null)->filter()->values()->all();

    expect($logs)->not->toBeEmpty()
        ->and($statuses)->toContain('in_review', 'approved', 'published');
});
