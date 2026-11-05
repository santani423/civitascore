<?php

require_once __DIR__.'/../Support/helpers.php';

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Modules\Admission\Database\Seeders\AtmaJayaAdmissionSeeder;
use Modules\Admission\Database\Seeders\AtmaJayaApplicantDemoSeeder;
use Modules\Admission\Models\AdmissionDecision;
use Modules\Admission\Models\Application;
use Modules\Admission\Models\DailyStat;
use Modules\Admission\Models\ManualPaymentProof;
use Modules\Admission\Models\OutboxEvent;
use Modules\Admission\Models\Refund;
use Modules\Admission\Models\ReRegistration;
use Modules\Admission\Models\SelectionRun;
use Modules\Admission\Models\StudentConversion;
use Modules\Admission\Models\Wave;
use Modules\Admission\Tests\Support\PmbWorld;

beforeEach(function (): void {
    Storage::fake(config('pmb.storage_disk', 'local'));
    config(['pmb.captcha.driver' => 'none']);
});

/** Seperti DatabaseSeeder: WithoutModelEvents aktif selama seluruh seeding. */
function seedAtmaJayaDemo(): void
{
    Model::withoutEvents(function (): void {
        (new AtmaJayaAdmissionSeeder)->run();
        (new AtmaJayaApplicantDemoSeeder)->run();
    });
}

/**
 * @param  Builder<Model>  $query
 * @return array<string, int> status => jumlah
 */
function pmbCountBy(Builder $query, string $column): array
{
    return $query->get()->groupBy(fn ($row) => $row->{$column}->value)->map->count()->sortKeys()->all();
}

it('seeds demo applicants at every PMB stage through the domain services', function (): void {
    $w = PmbWorld::create($this, 'UAJ');
    seedAtmaJayaDemo();
    $w->tenant();

    $application = fn (string $email) => Application::query()->whereHas('account', fn ($q) => $q->where('email', $email))->firstOrFail();

    expect(Wave::query()->orderBy('sequence')->get()->mapWithKeys(fn (Wave $wave) => [$wave->code => $wave->status->value])->all())->toBe(['G1' => 'announced', 'G2' => 'open', 'G3' => 'scheduled'])
        ->and(Application::query()->count())->toBe(37)
        ->and(SelectionRun::query()->get()->map(fn (SelectionRun $run) => $run->status->value)->unique()->values()->all())->toBe(['published'])
        // Gelombang 1: 19 peserta seleksi; cadangan p4 dipromosikan setelah p3 menolak tawaran.
        ->and(pmbCountBy(AdmissionDecision::query()->where('is_current', true), 'decision'))->toBe(['accepted' => 15, 'rejected' => 3, 'waitlisted' => 1])
        ->and(pmbCountBy(ReRegistration::query(), 'status'))->toBe([
            'awaiting_confirmation' => 2, 'confirmed' => 1, 'declined' => 2, 'enrolled' => 5, 'in_progress' => 2, 'revision_requested' => 1, 'submitted' => 2,
        ])
        ->and(StudentConversion::query()->where('status', 'succeeded')->whereNotNull('nim')->count())->toBe(5)
        ->and($application('putri.ayu.lestari@example.com')->currentDecision->source->value)->toBe('waitlist_promotion')
        ->and($application('felicia.angelina@example.com')->verification_status->value)->toBe('rejected')
        ->and($application('aulia.rahma@example.com')->form_status->value)->toBe('withdrawn')
        ->and($application('sekar.ayuningtyas@example.com')->form_status->value)->toBe('revision_requested')
        ->and($application('benedictus.raharjo@example.com')->selection_status->value)->toBe('scheduled')
        ->and(pmbCountBy(ManualPaymentProof::query(), 'status'))->toBe(['approved' => 23, 'rejected' => 1, 'submitted' => 3])
        ->and(Refund::query()->where('status', 'pending_approval')->count())->toBe(1)
        ->and(OutboxEvent::query()->where('status', 'failed')->count())->toBe(0)
        ->and(DailyStat::query()->count())->toBe(75 * 8);

    // Akun demo dapat dipakai langsung di portal pendaftar.
    $token = pmbOk($w->asPublic('POST', 'applicant/auth/login', [
        'login' => 'andreas.wijaya@example.com', 'password' => AtmaJayaAdmissionSeeder::DEMO_APPLICANT_PASSWORD,
    ]))->json('data.token');
    expect(pmbOk($w->asApplicant($token, 'GET', 'applications'))->json('data.0.current_stage'))->toBe('enrolled');

    // Dijalankan ulang: dilewati, tidak menggandakan pendaftar.
    seedAtmaJayaDemo();
    $w->tenant();
    expect(Application::query()->count())->toBe(37);
});
