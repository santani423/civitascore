<?php

// Helper bersama tes fitur PMB (dimuat dengan require_once agar aman di run paralel).

use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Modules\Admission\Models\ApplicantAccount;
use Modules\Admission\Tests\Support\PmbWorld;
use Modules\ApprovalWorkflow\Models\ApprovalRequest;

if (function_exists('pmbOk')) {
    return;
}

/** Menegaskan status 2xx dan menampilkan isi respons bila gagal. */
function pmbOk(TestResponse $response): TestResponse
{
    if ($response->status() >= 300) {
        throw new RuntimeException($response->baseRequest?->method().' '.$response->baseRequest?->getRequestUri().' → HTTP '.$response->status().': '.$response->getContent());
    }

    return $response;
}

/**
 * Menyiapkan konfigurasi PMB lengkap lewat endpoint admin (bukan langsung
 * ke model), sehingga controller, validasi, service & permission ikut teruji.
 *
 * @return array<string, string>
 */
function pmbConfigure(PmbWorld $w, int $registrationFee = 150000, bool $withCbt = false): array
{
    $admin = 'admission_administrator';
    $path = pmbOk($w->asStaff($admin, 'POST', 'admission-paths', ['code' => 'REG', 'name' => 'Reguler', 'category' => 'regular', 'requires_exam' => $withCbt, 'is_active' => true]))->json('data.id');

    $template = pmbOk($w->asStaff($admin, 'POST', 'form-templates', ['code' => 'FORM-REG', 'name' => 'Formulir Reguler']))->json('data.id');
    pmbOk($w->asStaff($admin, 'POST', "form-templates/{$template}/publish"));

    $committee = 'admission_selection_committee';
    $scheme = pmbOk($w->asStaff($committee, 'POST', 'selection-schemes', ['code' => 'SCH-REG', 'name' => 'Skema Reguler', 'passing_score' => 60]))->json('data.id');
    pmbOk($w->asStaff($committee, 'POST', "selection-schemes/{$scheme}/components", ['code' => 'ADM', 'name' => 'Administrasi', 'source' => 'administrative', 'score_type' => 'pass_fail', 'weight' => 0, 'is_mandatory' => true]));
    pmbOk($w->asStaff($committee, 'POST', "selection-schemes/{$scheme}/components", ['code' => 'RAPOR', 'name' => 'Nilai Rapor', 'source' => 'report_card', 'score_type' => 'numeric', 'raw_min' => 0, 'raw_max' => 100, 'weight' => $withCbt ? 50 : 100, 'is_mandatory' => true, 'tie_break_priority' => 1, 'config' => ['semesters' => [1, 2, 3, 4, 5]]]));

    if ($withCbt) {
        pmbOk($w->asStaff($committee, 'POST', "selection-schemes/{$scheme}/components", ['code' => 'TPA', 'name' => 'Tes Potensi Akademik (CBT)', 'source' => 'cbt', 'score_type' => 'numeric', 'raw_min' => 0, 'raw_max' => 100, 'weight' => 50, 'is_mandatory' => true]));
    }
    pmbOk($w->asStaff($committee, 'POST', "selection-schemes/{$scheme}/lock"));

    $period = pmbOk($w->asStaff($admin, 'POST', 'periods', ['entry_academic_term_id' => $w->term->getKey(), 'code' => 'PMB27', 'name' => 'PMB 2027/2028', 'starts_on' => now()->subDay()->toDateString(), 'ends_on' => now()->addMonths(6)->toDateString()]))->json('data.id');
    $wave = pmbOk($w->asStaff($admin, 'POST', "periods/{$period}/waves", [
        'code' => 'G1', 'name' => 'Gelombang 1', 'sequence' => 1,
        'registration_opens_at' => now()->subHour()->toIso8601String(), 'registration_closes_at' => now()->addDays(7)->toIso8601String(),
        'payment_deadline_at' => now()->addDays(8)->toIso8601String(), 'form_lock_at' => now()->addDays(9)->toIso8601String(),
        'announcement_at' => now()->addDays(10)->toIso8601String(),
        're_registration_opens_at' => now()->addDays(10)->toIso8601String(), 're_registration_closes_at' => now()->addDays(20)->toIso8601String(),
    ]))->json('data.id');
    $wavePath = pmbOk($w->asStaff($admin, 'POST', "waves/{$wave}/paths", [
        'admission_path_id' => $path, 'registration_fee' => $registrationFee, 'payment_timing' => 'after_submit', 'max_choices' => 2,
        'form_template_id' => $template, 'selection_scheme_id' => $scheme, 'quota_mode' => 'period', 'is_open' => true,
    ]))->json('data.id');

    pmbOk($w->asStaff($admin, 'PUT', "periods/{$period}/quotas/bulk", ['rows' => [
        ['admission_path_id' => $path, 'study_program_id' => $w->program->getKey(), 'study_system_id' => $w->system->getKey(), 'capacity' => 1, 'waitlist_capacity' => 2, 'is_offered' => true],
        ['admission_path_id' => $path, 'study_program_id' => $w->otherProgram->getKey(), 'study_system_id' => $w->system->getKey(), 'capacity' => 5, 'waitlist_capacity' => 0, 'is_offered' => true],
    ]]));

    $lookups = pmbOk($w->asStaff($admin, 'GET', 'lookups'))->json('data.document_types');
    $docId = fn (string $code) => collect($lookups)->firstWhere('code', $code)['id'];
    pmbOk($w->asStaff($admin, 'PUT', "wave-paths/{$wavePath}/document-requirements/bulk", ['rows' => [
        ['document_type_id' => $docId('PHOTO'), 'stage' => 'application', 'is_mandatory' => true],
        ['document_type_id' => $docId('ID_CARD'), 'stage' => 'application', 'is_mandatory' => true],
    ]]));

    pmbOk($w->asStaff($admin, 'PUT', "periods/{$period}/fee-components/bulk", ['rows' => [
        ['study_program_id' => $w->program->getKey(), 'study_system_id' => $w->system->getKey(), 'component_code' => 'SPP', 'name' => 'SPP Semester 1', 'amount' => 5000000, 'is_mandatory_for_enrollment' => true, 'installable' => false, 'is_published' => true],
    ]]));

    foreach (['privacy_policy' => 'Kebijakan Privasi', 'terms' => 'Ketentuan Pendaftaran'] as $type => $title) {
        $content = pmbOk($w->asStaff($admin, 'POST', 'contents', ['type' => $type, 'title' => $title, 'body' => '<p>Isi '.$title.'.</p>']))->json('data.id');
        pmbOk($w->asStaff($admin, 'POST', "contents/{$content}/publish"));
    }

    expect($w->asStaff($admin, 'GET', "periods/{$period}/publish-checklist")->json('data.ready'))->toBeTrue();
    pmbOk($w->asStaff($admin, 'POST', "periods/{$period}/publish"));

    return compact('path', 'template', 'scheme', 'period', 'wave', 'wavePath');
}

/** Registrasi + verifikasi email + login → token. */
function pmbApplicantToken(PmbWorld $w, string $email, string $name = 'Budi Santoso'): string
{
    pmbOk($w->asPublic('POST', 'applicant/auth/register', [
        'name' => $name, 'email' => $email, 'phone' => '0812'.random_int(10000000, 99999999),
        'password' => 'Rahasia-Kuat-2027!', 'password_confirmation' => 'Rahasia-Kuat-2027!',
        'consents' => ['privacy_policy' => true, 'terms' => true],
    ]));

    // Tautan verifikasi dikirim lewat email; tes menandai terverifikasi langsung.
    ApplicantAccount::query()->withoutGlobalScopes()->where('email', $email)->firstOrFail()->forceFill(['email_verified_at' => now(), 'status' => 'active'])->save();

    return pmbOk($w->asPublic('POST', 'applicant/auth/login', ['login' => $email, 'password' => 'Rahasia-Kuat-2027!']))->json('data.token');
}

/** Mengisi formulir lengkap dan mengirimkan → id aplikasi. */
function pmbSubmitApplication(PmbWorld $w, string $token, string $wavePath, float $reportScore, ?string $programId = null): string
{
    static $sequence = 0;
    $nik = '317101010105'.str_pad((string) ++$sequence, 4, '0', STR_PAD_LEFT);
    $application = pmbOk($w->asApplicant($token, 'POST', 'applications', ['wave_path_id' => $wavePath], ['Idempotency-Key' => (string) Str::uuid()]))->json('data');
    $id = $application['id'];
    $lock = fn () => $w->asApplicant($token, 'GET', "applications/{$id}")->json('data.lock_version');

    pmbOk($w->asApplicant($token, 'PATCH', "applications/{$id}", ['section' => 'identity', 'lock_version' => $lock(), 'data' => [
        'full_name' => 'Budi Santoso', 'nationality' => 'ID', 'nik' => $nik, 'birth_place' => 'Jakarta', 'birth_date' => '2005-01-01',
        'gender' => 'male', 'address_line' => 'Jl. Merdeka 1', 'city' => 'Jakarta Pusat', 'province' => 'DKI Jakarta',
    ]]));

    $education = pmbOk($w->asApplicant($token, 'POST', 'educations', ['level' => 'secondary', 'institution_name' => 'SMA Negeri 1', 'school_type' => 'sma', 'graduation_year' => now()->year, 'is_graduating' => true]))->json('data.id');
    $scores = [];

    foreach ([1, 2, 3, 4, 5] as $semester) {
        foreach (['MTK', 'BIN', 'BIG'] as $subject) {
            $scores[] = ['semester' => $semester, 'subject_code' => $subject, 'score' => $reportScore];
        }
    }

    pmbOk($w->asApplicant($token, 'PUT', "educations/{$education}/report-scores", ['scores' => $scores]));
    pmbOk($w->asApplicant($token, 'PATCH', "applications/{$id}", ['section' => 'education', 'lock_version' => $lock(), 'data' => ['primary_education_id' => $education]]));
    pmbOk($w->asApplicant($token, 'POST', 'guardians', ['relationship' => 'mother', 'full_name' => 'Siti Aminah', 'phone' => '081298765432']));
    pmbOk($w->asApplicant($token, 'PATCH', "applications/{$id}", ['section' => 'supporting', 'lock_version' => $lock(), 'data' => ['info_source' => 'website']]));
    pmbOk($w->asApplicant($token, 'PUT', "applications/{$id}/choices", ['lock_version' => $lock(), 'choices' => [
        ['priority' => 1, 'study_program_id' => $programId ?? $w->program->getKey(), 'study_system_id' => $w->system->getKey()],
    ]]));

    foreach (pmbOk($w->asApplicant($token, 'GET', "applications/{$id}/documents"))->json('data') as $document) {
        $file = $document['document_type']['code'] === 'PHOTO' ? UploadedFile::fake()->image('foto.jpg', 450, 600) : UploadedFile::fake()->image('ktp.png', 800, 500);
        pmbOk($w->asApplicant($token, 'POST', "applications/{$id}/documents/{$document['id']}/versions", ['file' => $file]));
    }

    $completeness = pmbOk($w->asApplicant($token, 'GET', "applications/{$id}/completeness"))->json('data');
    expect($completeness['can_submit'])->toBeTrue(json_encode($completeness));

    $submitted = pmbOk($w->asApplicant($token, 'POST', "applications/{$id}/submit", ['declarations' => [], 'lock_version' => $lock()], ['Idempotency-Key' => (string) Str::uuid()]));
    expect($submitted->json('data.registration_number'))->not->toBeNull();

    return $id;
}

/** Bayar biaya pendaftaran via transfer manual, dikonfirmasi keuangan, lalu diverifikasi petugas. */
function pmbPayAndVerify(PmbWorld $w, string $token, string $appId): void
{
    pmbPayRegistration($w, $token, $appId);
    pmbVerify($w, $appId);
}

/** Bayar biaya pendaftaran via transfer manual dan dikonfirmasi petugas keuangan. */
function pmbPayRegistration(PmbWorld $w, string $token, string $appId): void
{
    $invoice = pmbOk($w->asApplicant($token, 'GET', "applications/{$appId}/invoices"))->json('data.0');
    $tx = pmbOk($w->asApplicant($token, 'POST', "invoices/{$invoice['id']}/transactions", ['channel' => 'manual_transfer'], ['Idempotency-Key' => (string) Str::uuid()]))->json('data.transaction');
    $proof = pmbOk($w->asApplicant($token, 'POST', "transactions/{$tx['id']}/manual-proof", [
        'file' => UploadedFile::fake()->image('bukti.jpg'), 'sender_bank' => 'BCA', 'sender_name' => 'Pengirim', 'transfer_date' => now()->toDateString(), 'transferred_amount' => $tx['amount'],
    ], ['Idempotency-Key' => (string) Str::uuid()]));
    $proofId = collect(pmbOk($w->asStaff('finance_administrator', 'GET', 'manual-proofs'))->json('data'))->firstWhere('invoice_number', $invoice['invoice_number'])['id'];
    pmbOk($w->asStaff('finance_administrator', 'POST', "manual-proofs/{$proofId}/decision", ['approve' => true], ['Idempotency-Key' => (string) Str::uuid()]));
}

/** Klaim, setujui semua dokumen, verifikasi aplikasi. */
function pmbVerify(PmbWorld $w, string $appId): void
{
    pmbOk($w->asStaff('admission_verifier', 'POST', "applications/{$appId}/claim"));

    foreach (pmbOk($w->asStaff('admission_verifier', 'GET', "applications/{$appId}/documents"))->json('data.documents') as $doc) {
        pmbOk($w->asStaff('admission_verifier', 'POST', "application-documents/{$doc['id']}/reviews", ['decision' => 'approved', 'version_id' => $doc['current_version_id']]));
    }

    $lockVersion = pmbOk($w->asStaff('admission_verifier', 'GET', "applications/{$appId}"))->json('data.lock_version');
    pmbOk($w->asStaff('admission_verifier', 'POST', "applications/{$appId}/verify", ['lock_version' => $lockVersion]));
}

/** Menyetujui langkah approval yang sedang aktif atas nama user. */
function pmbApproveCurrentStep(PmbWorld $w, TestCase $test, string $approvalRequestId, User $user): TestResponse
{
    $w->tenant();
    $request = ApprovalRequest::query()->findOrFail($approvalRequestId);
    app(TenantContext::class)->setUniversityId(null);
    app('auth')->forgetGuards();
    $test->flushHeaders();

    return $test->actingAs($user)->postJson("/api/v1/approval-request-steps/{$request->current_step_id}/approve", ['comment' => 'Setuju'], ['X-University-ID' => $w->university->getKey()]);
}
