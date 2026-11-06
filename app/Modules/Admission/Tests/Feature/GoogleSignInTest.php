<?php

require_once __DIR__.'/../Support/helpers.php';

use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Modules\Admission\Contracts\GoogleIdentityProvider;
use Modules\Admission\Integrations\Google\GoogleIdentity;
use Modules\Admission\Models\AccountConsent;
use Modules\Admission\Models\ApplicantAccount;
use Modules\Admission\Services\Account\GoogleAuthService;
use Modules\Admission\Services\Privacy\RetentionService;
use Modules\Admission\Tests\Support\FakeGoogleIdentityProvider;
use Modules\Admission\Tests\Support\PmbWorld;
use Modules\Tenancy\Models\University;

/*
| Google Sign-In pendaftar PMB — skenario 1–10 laporan implementasi.
| Google diganti FakeGoogleIdentityProvider; ID token asli diuji di
| Unit/GoogleIdTokenVerifierTest.
*/

const PMB_GOOGLE_VERIFIER = 'verifier-tab-pendaftar-0123456789-abcdefghijk';

beforeEach(function (): void {
    Storage::fake(config('pmb.storage_disk', 'local'));
    config(['pmb.captcha.driver' => 'none', 'pmb.frontend_url' => 'https://pmb.example.test']);

    $this->google = new FakeGoogleIdentityProvider;
    $this->app->instance(GoogleIdentityProvider::class, $this->google);
    $this->world = PmbWorld::create($this);
});

function pmbGoogleIdentity(string $email, bool $verified = true, string $sub = '1098765432101234567890', string $name = 'Maria Angelina'): GoogleIdentity
{
    return new GoogleIdentity($sub, $email, $verified, $name, 'https://lh3.googleusercontent.com/a/foto');
}

/** Langkah 1: SPA meminta URL Google → state yang dikirim ke Google. */
function pmbGoogleStart(PmbWorld $w, string $verifier = PMB_GOOGLE_VERIFIER): string
{
    $url = pmbOk($w->asPublic('GET', 'applicant/auth/google/redirect?client_challenge='.GoogleAuthService::s256($verifier)))->json('data.authorization_url');
    parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

    return (string) $query['state'];
}

/**
 * Langkah 2: Google mengarahkan browser ke callback (tanpa header kampus).
 *
 * @param  array<string, string>  $query
 * @return array<string, string> isi fragmen URL frontend
 */
function pmbGoogleCallback(TestCase $test, array $query): array
{
    app('auth')->forgetGuards();
    $test->flushHeaders();
    app(TenantContext::class)->setUniversityId(null);

    $response = $test->get('/api/v1/pmb/applicant/auth/google/callback?'.http_build_query($query));
    $response->assertRedirect();
    $location = (string) $response->headers->get('Location');

    // Tujuan redirect selalu URL frontend tetap — bukan dari input (anti open redirect).
    expect($location)->toStartWith('https://pmb.example.test/pmb/masuk/google#');
    parse_str((string) parse_url($location, PHP_URL_FRAGMENT), $fragment);

    return $fragment;
}

/** Langkah 1+2 lengkap → tiket. */
function pmbGoogleTicket(TestCase $test, PmbWorld $w, string $verifier = PMB_GOOGLE_VERIFIER): string
{
    $fragment = pmbGoogleCallback($test, ['state' => pmbGoogleStart($w, $verifier), 'code' => 'kode-otorisasi']);
    expect($fragment)->toHaveKey('ticket');

    return $fragment['ticket'];
}

function pmbGoogleAccount(string $email): ?ApplicantAccount
{
    return ApplicantAccount::query()->withoutGlobalScopes()->where('email', $email)->first();
}

/** @return array<string, mixed> */
function pmbGoogleConsents(): array
{
    return ['privacy_policy' => true, 'terms' => true];
}

it('keeps email/password registration and login working alongside Google (skenario 1, 2)', function (): void {
    $w = $this->world;
    $token = pmbApplicantToken($w, 'email.biasa@example.test');

    $me = pmbOk($w->asApplicant($token, 'GET', 'me'))->json('data.account');
    expect($me['auth_provider'])->toBe('email')
        ->and($me['google_linked'])->toBeFalse()
        ->and($me['has_password'])->toBeTrue();

    expect(pmbGoogleAccount('email.biasa@example.test')->auth_provider)->toBe('email');
});

it('registers a new applicant with Google, then signs in again with Google (skenario 3, 4, 10)', function (): void {
    $w = $this->world;
    $this->google->identity = pmbGoogleIdentity('maria.baru@gmail.com');

    $ticket = pmbGoogleTicket($this, $w);

    // PKCE ke Google: code_verifier yang ditukar cocok dengan code_challenge yang dikirim, nonce diteruskan.
    expect(GoogleAuthService::s256($this->google->exchange['code_verifier']))->toBe($this->google->authorization['code_challenge'])
        ->and($this->google->exchange['nonce'])->toBe($this->google->authorization['nonce']);

    $pending = pmbOk($w->asPublic('POST', 'applicant/auth/google/session', ['ticket' => $ticket, 'client_verifier' => PMB_GOOGLE_VERIFIER]))->json('data');
    expect($pending)->toMatchArray(['status' => 'registration_required', 'email' => 'maria.baru@gmail.com', 'name' => 'Maria Angelina'])
        ->and(pmbGoogleAccount('maria.baru@gmail.com'))->toBeNull();

    // Persetujuan wajib, sama seperti registrasi email.
    $w->asPublic('POST', 'applicant/auth/google/register', ['ticket' => $ticket, 'client_verifier' => PMB_GOOGLE_VERIFIER, 'name' => 'Maria Angelina', 'consents' => ['privacy_policy' => true]])
        ->assertStatus(422)->assertJsonValidationErrors(['consents.terms'], 'errors');

    $session = pmbOk($w->asPublic('POST', 'applicant/auth/google/register', [
        'ticket' => $ticket, 'client_verifier' => PMB_GOOGLE_VERIFIER, 'name' => 'Maria Angelina Putri', 'phone' => '081233344455',
        'info_source' => 'social_media', 'consents' => pmbGoogleConsents() + ['marketing_optional' => true],
    ]))->assertStatus(201)->json('data');

    expect($session['status'])->toBe('authenticated')
        ->and($session['token'])->toBeString()
        ->and($session['account'])->toMatchArray(['email' => 'maria.baru@gmail.com', 'name' => 'Maria Angelina Putri', 'status' => 'active', 'email_verified' => true, 'auth_provider' => 'google', 'google_linked' => true, 'has_password' => false]);

    $account = pmbGoogleAccount('maria.baru@gmail.com');
    expect($account->google_id)->toBe('1098765432101234567890')
        ->and($account->university_id)->toBe($w->university->getKey())
        ->and($account->phone)->toBe('+6281233344455')
        ->and($account->email_verified_at)->not->toBeNull()
        ->and($account->last_login_at)->not->toBeNull()
        ->and(AccountConsent::query()->withoutGlobalScopes()->where('applicant_account_id', $account->getKey())->pluck('consent_type')->all())
        ->toEqualCanonicalizing(['privacy_policy', 'terms', 'marketing_optional']);

    // Skenario 10: token dipakai untuk portal & aksi yang mewajibkan akun terverifikasi.
    pmbOk($w->asApplicant($session['token'], 'GET', 'me'))->assertJsonPath('data.account.email', 'maria.baru@gmail.com');
    pmbOk($w->asApplicant($session['token'], 'GET', 'applications'));

    // Tiket registrasi sekali pakai.
    $w->asPublic('POST', 'applicant/auth/google/session', ['ticket' => $ticket, 'client_verifier' => PMB_GOOGLE_VERIFIER])
        ->assertStatus(422)->assertJsonPath('errors.code', 'GOOGLE_SESSION_INVALID');

    // Skenario 4: masuk kembali dengan Google → langsung terautentikasi, tanpa akun baru.
    $again = pmbOk($w->asPublic('POST', 'applicant/auth/google/session', ['ticket' => pmbGoogleTicket($this, $w), 'client_verifier' => PMB_GOOGLE_VERIFIER, 'device_name' => 'Chrome']))->json('data');
    expect($again['status'])->toBe('authenticated')
        ->and($again['account']['id'])->toBe($account->getKey())
        ->and(ApplicantAccount::query()->withoutGlobalScopes()->where('email', 'maria.baru@gmail.com')->count())->toBe(1);
    pmbOk($w->asApplicant($again['token'], 'GET', 'me'));

    // Akun Google dapat membuat sandi lewat "Lupa kata sandi" (alur reset yang sudah ada).
    expect(pmbGoogleAccount('maria.baru@gmail.com')->has_password)->toBeFalse();
});

it('links Google to an existing email account only after the old password is confirmed (skenario 5)', function (): void {
    $w = $this->world;
    $oldToken = pmbApplicantToken($w, 'lama@example.test', 'Budi Lama');
    $before = pmbGoogleAccount('lama@example.test');
    $this->google->identity = pmbGoogleIdentity('lama@example.test', name: 'Budi di Google');

    $ticket = pmbGoogleTicket($this, $w);
    $info = pmbOk($w->asPublic('POST', 'applicant/auth/google/session', ['ticket' => $ticket, 'client_verifier' => PMB_GOOGLE_VERIFIER]))->json('data');

    // Tidak otomatis login/ditautkan hanya karena email sama.
    expect($info)->toMatchArray(['status' => 'link_required', 'email' => 'lama@example.test'])
        ->and($info)->not->toHaveKey('token')
        ->and(pmbGoogleAccount('lama@example.test')->google_id)->toBeNull();

    $w->asPublic('POST', 'applicant/auth/google/link', ['ticket' => $ticket, 'client_verifier' => PMB_GOOGLE_VERIFIER, 'password' => 'salah-sandi-1'])
        ->assertStatus(401)->assertJsonPath('errors.code', 'INVALID_CREDENTIALS');
    expect(pmbGoogleAccount('lama@example.test')->failed_login_count)->toBe(1)
        ->and(pmbGoogleAccount('lama@example.test')->google_id)->toBeNull();

    $linked = pmbOk($w->asPublic('POST', 'applicant/auth/google/link', ['ticket' => $ticket, 'client_verifier' => PMB_GOOGLE_VERIFIER, 'password' => 'Rahasia-Kuat-2027!']))->json('data');
    expect($linked['status'])->toBe('authenticated')
        ->and($linked['account'])->toMatchArray(['id' => $before->getKey(), 'google_linked' => true, 'auth_provider' => 'email', 'has_password' => true, 'name' => 'Budi Lama']);

    $after = pmbGoogleAccount('lama@example.test');
    expect($after->google_id)->toBe('1098765432101234567890')
        ->and($after->password)->toBe($before->password) // sandi lama tidak ditimpa
        ->and(Hash::check('Rahasia-Kuat-2027!', $after->password))->toBeTrue()
        ->and($after->failed_login_count)->toBe(0)
        ->and(ApplicantAccount::query()->withoutGlobalScopes()->where('email', 'lama@example.test')->count())->toBe(1);

    // Sesi lama tetap berlaku, login email/sandi tetap berfungsi, dan Google kini langsung masuk.
    pmbOk($w->asApplicant($oldToken, 'GET', 'me'));
    pmbOk($w->asPublic('POST', 'applicant/auth/login', ['login' => 'lama@example.test', 'password' => 'Rahasia-Kuat-2027!']));
    $direct = pmbOk($w->asPublic('POST', 'applicant/auth/google/session', ['ticket' => pmbGoogleTicket($this, $w), 'client_verifier' => PMB_GOOGLE_VERIFIER]))->json('data');
    expect($direct['status'])->toBe('authenticated')->and($direct['account']['id'])->toBe($before->getKey());
});

it('verifies a not-yet-verified email account when it is linked with Google and its password', function (): void {
    $w = $this->world;
    pmbOk($w->asPublic('POST', 'applicant/auth/register', [
        'name' => 'Belum Verifikasi', 'email' => 'belum@example.test', 'password' => 'Rahasia-Kuat-2027!', 'password_confirmation' => 'Rahasia-Kuat-2027!', 'consents' => pmbGoogleConsents(),
    ]));
    expect(pmbGoogleAccount('belum@example.test')->status->value)->toBe('unverified');
    $this->google->identity = pmbGoogleIdentity('belum@example.test');

    $ticket = pmbGoogleTicket($this, $w);
    expect(pmbOk($w->asPublic('POST', 'applicant/auth/google/session', ['ticket' => $ticket, 'client_verifier' => PMB_GOOGLE_VERIFIER]))->json('data.status'))->toBe('link_required');

    $linked = pmbOk($w->asPublic('POST', 'applicant/auth/google/link', ['ticket' => $ticket, 'client_verifier' => PMB_GOOGLE_VERIFIER, 'password' => 'Rahasia-Kuat-2027!']))->json('data.account');
    expect($linked)->toMatchArray(['status' => 'active', 'email_verified' => true, 'google_linked' => true]);
});

it('handles a cancelled consent, an unverified Google email, and failed callbacks (skenario 6, 7, 8)', function (): void {
    $w = $this->world;

    // 6. Pengguna menekan "Batal" di layar Google.
    $state = pmbGoogleStart($w);
    expect(pmbGoogleCallback($this, ['state' => $state, 'error' => 'access_denied']))->toBe(['error' => 'cancelled']);
    // State sekali pakai: diulang → ditolak.
    expect(pmbGoogleCallback($this, ['state' => $state, 'code' => 'x']))->toBe(['error' => 'state_invalid']);

    // 7. Email Google belum terverifikasi → tidak ada akun yang dibuat/ditautkan.
    $this->google->identity = pmbGoogleIdentity('tidak.terverifikasi@example.test', verified: false);
    expect(pmbGoogleCallback($this, ['state' => pmbGoogleStart($w), 'code' => 'x']))->toBe(['error' => 'email_unverified'])
        ->and(pmbGoogleAccount('tidak.terverifikasi@example.test'))->toBeNull();

    // 8. Penukaran code / validasi ID token gagal, state palsu, atau error lain dari Google.
    $this->google->fail = true;
    expect(pmbGoogleCallback($this, ['state' => pmbGoogleStart($w), 'code' => 'x']))->toBe(['error' => 'google_failed'])
        ->and(pmbGoogleCallback($this, ['state' => 'state-palsu', 'code' => 'x']))->toBe(['error' => 'state_invalid'])
        ->and(pmbGoogleCallback($this, ['code' => 'x']))->toBe(['error' => 'state_invalid'])
        ->and(pmbGoogleCallback($this, ['state' => pmbGoogleStart($w), 'error' => 'server_error']))->toBe(['error' => 'google_error'])
        ->and(pmbGoogleCallback($this, ['state' => pmbGoogleStart($w)]))->toBe(['error' => 'google_error']);
});

it('never creates a duplicate account for an email that is already registered (skenario 9)', function (): void {
    $w = $this->world;
    $this->google->identity = pmbGoogleIdentity('ganda@gmail.com');
    $ticket = pmbGoogleTicket($this, $w);
    pmbOk($w->asPublic('POST', 'applicant/auth/google/session', ['ticket' => $ticket, 'client_verifier' => PMB_GOOGLE_VERIFIER]));

    // Sementara formulir Google terbuka, email yang sama didaftarkan lewat formulir email.
    pmbOk($w->asPublic('POST', 'applicant/auth/register', [
        'name' => 'Pendaftar Lain', 'email' => 'Ganda@Gmail.com', 'password' => 'Rahasia-Kuat-2027!', 'password_confirmation' => 'Rahasia-Kuat-2027!', 'consents' => pmbGoogleConsents(),
    ]));

    $w->asPublic('POST', 'applicant/auth/google/register', ['ticket' => $ticket, 'client_verifier' => PMB_GOOGLE_VERIFIER, 'name' => 'Pendaftar Google', 'consents' => pmbGoogleConsents()])
        ->assertStatus(409)->assertJsonPath('errors.code', 'ACCOUNT_EXISTS');

    // Registrasi email untuk email yang sudah ada: respons identik (anti-enumerasi) tanpa akun baru.
    pmbOk($w->asPublic('POST', 'applicant/auth/register', [
        'name' => 'Pendaftar Ketiga', 'email' => 'ganda@gmail.com', 'password' => 'Rahasia-Kuat-2027!', 'password_confirmation' => 'Rahasia-Kuat-2027!', 'consents' => pmbGoogleConsents(),
    ]))->assertStatus(201)->assertJsonPath('data.status', 'verification_sent');

    expect(ApplicantAccount::query()->withoutGlobalScopes()->where('email', 'ganda@gmail.com')->count())->toBe(1)
        ->and(pmbGoogleAccount('ganda@gmail.com')->google_id)->toBeNull();

    // Nomor HP milik akun lain juga ditolak saat melengkapi pendaftaran Google.
    $this->google->identity = pmbGoogleIdentity('hp.ganda@gmail.com', sub: '2000000000000000000001');
    $phoneOwner = pmbGoogleAccount('ganda@gmail.com');
    $phoneOwner->forceFill(['phone' => '+6281200000001'])->save();
    $ticket = pmbGoogleTicket($this, $w);
    $w->asPublic('POST', 'applicant/auth/google/register', ['ticket' => $ticket, 'client_verifier' => PMB_GOOGLE_VERIFIER, 'name' => 'Pendaftar HP', 'phone' => '081200000001', 'consents' => pmbGoogleConsents()])
        ->assertStatus(422)->assertJsonValidationErrors(['phone'], 'errors');
});

it('binds the ticket to the browser tab that started the flow and to its campus', function (): void {
    $w = $this->world;
    $this->google->identity = pmbGoogleIdentity('korban@gmail.com');
    $ticket = pmbGoogleTicket($this, $w);

    // Login CSRF: tiket/tautan callback milik penyerang dibuka di browser korban (verifier berbeda).
    $w->asPublic('POST', 'applicant/auth/google/session', ['ticket' => $ticket, 'client_verifier' => 'verifier-lain-milik-korban-0123456789-abcdefg'])
        ->assertStatus(422)->assertJsonPath('errors.code', 'GOOGLE_SESSION_INVALID');
    $w->asPublic('POST', 'applicant/auth/google/session', ['ticket' => 'tiket-tebakan', 'client_verifier' => PMB_GOOGLE_VERIFIER])
        ->assertStatus(422);

    // Tiket kampus A tidak berlaku di kampus B.
    University::factory()->create(['code' => 'LAIN', 'is_active' => true]);
    app('auth')->forgetGuards();
    $this->flushHeaders();
    app(TenantContext::class)->setUniversityId(null);
    $this->json('POST', '/api/v1/pmb/applicant/auth/google/session', ['ticket' => $ticket, 'client_verifier' => PMB_GOOGLE_VERIFIER], ['X-University-Code' => 'LAIN'])
        ->assertStatus(422)->assertJsonPath('errors.code', 'GOOGLE_SESSION_INVALID');

    // Tiket asli masih bisa dipakai oleh tab yang benar.
    expect(pmbOk($w->asPublic('POST', 'applicant/auth/google/session', ['ticket' => $ticket, 'client_verifier' => PMB_GOOGLE_VERIFIER]))->json('data.status'))->toBe('registration_required');
});

it('rejects suspended accounts and email accounts already linked to another Google account', function (): void {
    $w = $this->world;
    pmbApplicantToken($w, 'dibekukan@example.test');
    pmbGoogleAccount('dibekukan@example.test')->forceFill(['status' => 'suspended'])->save();
    $this->google->identity = pmbGoogleIdentity('dibekukan@example.test');
    expect(pmbGoogleCallback($this, ['state' => pmbGoogleStart($w), 'code' => 'x']))->toBe(['error' => 'account_inactive']);

    pmbApplicantToken($w, 'tertaut@example.test');
    pmbGoogleAccount('tertaut@example.test')->forceFill(['google_id' => 'sub-akun-google-lama'])->save();
    $this->google->identity = pmbGoogleIdentity('tertaut@example.test', sub: 'sub-akun-google-baru');
    expect(pmbGoogleCallback($this, ['state' => pmbGoogleStart($w), 'code' => 'x']))->toBe(['error' => 'account_conflict']);
});

it('hides Google when unconfigured and never exposes the client secret', function (): void {
    $w = $this->world;
    config(['services.google.client_secret' => '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or']);

    $site = pmbOk($w->asPublic('GET', 'public/site'));
    expect($site->json('data.auth.google'))->toBeTrue()
        ->and($site->getContent())->not->toContain('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or');
    $redirect = pmbOk($w->asPublic('GET', 'applicant/auth/google/redirect?client_challenge='.GoogleAuthService::s256(PMB_GOOGLE_VERIFIER)));
    expect($redirect->getContent())->not->toContain('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or');

    // client_challenge wajib berformat PKCE S256.
    $w->asPublic('GET', 'applicant/auth/google/redirect?client_challenge=pendek')->assertStatus(422);

    $this->google->configured = false;
    $w->asPublic('GET', 'applicant/auth/google/redirect?client_challenge='.GoogleAuthService::s256(PMB_GOOGLE_VERIFIER))
        ->assertStatus(503)->assertJsonPath('errors.code', 'GOOGLE_NOT_CONFIGURED');
});

it('clears the Google identity when an account is anonymized by retention', function (): void {
    $w = $this->world;
    $this->google->identity = pmbGoogleIdentity('retensi@gmail.com');
    $ticket = pmbGoogleTicket($this, $w);
    pmbOk($w->asPublic('POST', 'applicant/auth/google/register', ['ticket' => $ticket, 'client_verifier' => PMB_GOOGLE_VERIFIER, 'name' => 'Pendaftar Retensi', 'consents' => pmbGoogleConsents()]));

    $w->tenant();
    app(RetentionService::class)->anonymize(pmbGoogleAccount('retensi@gmail.com'));

    expect(ApplicantAccount::query()->withoutGlobalScopes()->whereNotNull('google_id')->count())->toBe(0);
});
