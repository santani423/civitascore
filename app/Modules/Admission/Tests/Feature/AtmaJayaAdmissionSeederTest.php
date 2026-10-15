<?php

require_once __DIR__.'/../Support/helpers.php';

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Admission\Database\Seeders\AtmaJayaAdmissionSeeder;
use Modules\Admission\Models\CbtQuestion;
use Modules\Admission\Models\Content;
use Modules\Admission\Models\ExamSession;
use Modules\Admission\Models\FeeComponent;
use Modules\Admission\Models\Period;
use Modules\Admission\Models\Quota;
use Modules\Admission\Models\WavePath;
use Modules\Admission\Tests\Support\PmbWorld;

beforeEach(function (): void {
    Storage::fake(config('pmb.storage_disk', 'local'));
    config(['pmb.captcha.driver' => 'none']);
});

/** DatabaseSeeder memakai WithoutModelEvents — seeder harus tetap benar tanpa event model. */
function seedAtmaJayaAdmission(): void
{
    Model::withoutEvents(fn () => (new AtmaJayaAdmissionSeeder)->run());
}

it('seeds a published PMB configuration that the public site, admin and applicant portal can use', function (): void {
    $w = PmbWorld::create($this, 'UAJ');
    seedAtmaJayaAdmission();

    $programs = collect(pmbOk($w->asPublic('GET', 'public/programs'))->json('data'));
    expect($programs)->toHaveCount(7)
        ->and($programs->firstWhere('slug', 'teknik-informatika')['paths'])->toEqualCanonicalizing(['Jalur Rapor', 'Ujian Saringan Masuk (CBT)', 'Jalur Prestasi']);

    $period = pmbOk($w->asPublic('GET', 'public/periods/active'))->json('data.0');
    expect($period['code'])->toBe('PMB2027')
        ->and(collect($period['waves'])->pluck('status', 'code')->all())->toBe(['G1' => 'open', 'G2' => 'scheduled']);

    expect(pmbOk($w->asPublic('GET', 'public/paths'))->json('data'))->toHaveCount(3)
        ->and(pmbOk($w->asPublic('GET', 'public/paths/usm'))->json('data.waves.0.selection_components'))->toEqualCanonicalizing(['Seleksi Administrasi', 'Tes Potensi Akademik (CBT)', 'Nilai Rapor'])
        ->and(collect(pmbOk($w->asPublic('GET', 'public/programs/teknik-informatika'))->json('data.fees'))->where('code', 'DPP')->pluck('amount')->sort()->values()->all())->toBe(['12500000.00', '25000000.00'])
        ->and(pmbOk($w->asPublic('GET', 'public/contents?type=faq'))->json('data'))->toHaveCount(6)
        ->and(pmbOk($w->asPublic('GET', 'public/site'))->json('data.legal.terms'))->not->toBeNull();

    $w->tenant();
    $periodId = Period::query()->where('code', 'PMB2027')->value('id');
    $wavePathId = WavePath::query()->whereHas('wave', fn ($q) => $q->where('code', 'G1'))->whereHas('admissionPath', fn ($q) => $q->where('code', 'RAPOR'))->value('id');

    $admin = User::query()->where('email', 'pmb.admin@atmajaya.com')->firstOrFail();
    expect(pmbOk($w->asStaff($admin, 'GET', "periods/{$periodId}/publish-checklist"))->json('data.ready'))->toBeTrue();

    $token = pmbOk($w->asPublic('POST', 'applicant/auth/login', [
        'login' => AtmaJayaAdmissionSeeder::DEMO_APPLICANT_EMAIL, 'password' => AtmaJayaAdmissionSeeder::DEMO_APPLICANT_PASSWORD,
    ]))->json('data.token');
    pmbOk($w->asApplicant($token, 'POST', 'applications', ['wave_path_id' => $wavePathId], ['Idempotency-Key' => (string) Str::uuid()]));
});

it('can be re-run without duplicating data', function (): void {
    $w = PmbWorld::create($this, 'UAJ');
    $counts = function () use ($w): array {
        $w->tenant();

        return [Quota::query()->count(), FeeComponent::query()->count(), Content::query()->count(), WavePath::query()->count(), ExamSession::query()->count(), CbtQuestion::query()->count()];
    };

    seedAtmaJayaAdmission();
    expect($counts())->toBe([25, 34, 16, 6, 8, 24]);

    seedAtmaJayaAdmission();
    expect($counts())->toBe([25, 34, 16, 6, 8, 24]);
});
