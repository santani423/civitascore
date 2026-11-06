<?php

namespace Modules\Admission\Services\Account;

use App\Support\Tenancy\TenantContext;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\NewAccessToken;
use Modules\Admission\Contracts\GoogleIdentityProvider;
use Modules\Admission\Enums\AccountStatus;
use Modules\Admission\Enums\ConsentType;
use Modules\Admission\Enums\ContentType;
use Modules\Admission\Exceptions\DomainRuleException;
use Modules\Admission\Integrations\Google\GoogleAuthException;
use Modules\Admission\Integrations\Google\GoogleIdentity;
use Modules\Admission\Models\ApplicantAccount;
use Modules\Admission\Outbox\OutboxRecorder;
use Modules\Admission\Services\Configuration\ContentService;
use Modules\AuditLog\Enums\AuditAction;
use Modules\AuditLog\Services\AuditLogService;
use Modules\Tenancy\Models\University;

/**
 * Daftar/masuk pendaftar PMB dengan Google (OAuth 2.0 authorization code +
 * PKCE, OpenID Connect). Alur:
 *
 *  1. start()    SPA mengirim `client_challenge` = SHA-256(verifier acak yang
 *                disimpan di sessionStorage tab tersebut). Backend membuat
 *                state, nonce, dan PKCE verifier (cache, 10 menit, sekali pakai)
 *                lalu mengembalikan URL Google.
 *  2. callback() Google → backend: state diambil-dan-dihapus, code ditukar,
 *                ID token divalidasi. Hasilnya disimpan sebagai "tiket" acak
 *                (cache) dan browser diarahkan ke URL frontend TETAP
 *                (pmb.frontend_url + /pmb/masuk/google#ticket=…) — tidak ada
 *                URL tujuan dari input, sehingga tidak ada open redirect, dan
 *                token sesi tidak pernah muncul di URL.
 *  3. resume()/link()/register()  SPA menukar tiket + verifier-nya. Verifier
 *                mengikat tiket ke tab yang memulai alur: tautan callback/tiket
 *                milik orang lain tidak bisa dipakai untuk login-CSRF.
 *
 * Kebijakan akun (tidak pernah membuat akun ganda per email per kampus):
 *  - `google_id` (klaim `sub`) sudah tertaut → langsung masuk.
 *  - Email Google sama dengan akun lama yang belum tertaut → TIDAK otomatis
 *    ditautkan; pendaftar harus memasukkan kata sandi akun lama (bukti memiliki
 *    kedua identitas). Ini menutup "pre-account hijacking" (orang lain
 *    mendaftarkan email korban lebih dulu). Sandi lama tidak diubah.
 *  - Email belum terdaftar → pendaftar melengkapi nama sesuai ijazah dan
 *    menyetujui kebijakan privasi & ketentuan, lalu akun dibuat (email sudah
 *    diverifikasi Google; sandi acak, `has_password = false`).
 *  - Email Google yang belum diverifikasi Google selalu ditolak.
 */
class GoogleAuthService
{
    public const FRONTEND_CALLBACK_PATH = '/pmb/masuk/google';

    private const STATE_TTL_MINUTES = 10;

    private const LOGIN_TICKET_TTL_SECONDS = 120;

    private const PENDING_TICKET_TTL_MINUTES = 15;

    public function __construct(
        private readonly TenantContext $tenant,
        private readonly GoogleIdentityProvider $google,
        private readonly AccountService $accounts,
        private readonly OutboxRecorder $outbox,
        private readonly AuditLogService $audit,
    ) {}

    public function isAvailable(): bool
    {
        return $this->google->isConfigured();
    }

    // ------------------------------------------------------------------ 1. Redirect

    public function start(string $clientChallenge): string
    {
        if (! $this->google->isConfigured()) {
            throw new DomainRuleException('Masuk dengan Google belum tersedia. Silakan gunakan email dan kata sandi.', 'GOOGLE_NOT_CONFIGURED', 503);
        }

        $state = Str::random(48);
        $nonce = Str::random(48);
        $codeVerifier = Str::random(64);

        Cache::put($this->stateKey($state), [
            'university_id' => (string) $this->tenant->universityId(),
            'nonce' => $nonce,
            'code_verifier' => $codeVerifier,
            'client_challenge' => $clientChallenge,
        ], now()->addMinutes(self::STATE_TTL_MINUTES));

        return $this->google->authorizationUrl($state, $nonce, self::s256($codeVerifier));
    }

    // ------------------------------------------------------------------ 2. Callback

    /** Selalu mengembalikan URL frontend tetap; kegagalan dibawa sebagai kode `error` di fragmen. */
    public function callback(?string $state, ?string $code, ?string $error): string
    {
        $flow = is_string($state) && $state !== '' ? Cache::pull($this->stateKey($state)) : null;

        if (! is_array($flow)) {
            return $this->frontend(['error' => 'state_invalid']);
        }

        if (is_string($error) && $error !== '') {
            return $this->frontend(['error' => $error === 'access_denied' ? 'cancelled' : 'google_error']);
        }

        $university = University::query()->whereKey((string) $flow['university_id'])->first();

        if (! is_string($code) || $code === '' || $university === null || ! $university->is_active) {
            return $this->frontend(['error' => 'google_error']);
        }

        $this->tenant->setUniversityId((string) $university->getKey());

        try {
            $identity = $this->google->identityFromCode($code, (string) $flow['code_verifier'], (string) $flow['nonce']);
        } catch (GoogleAuthException $e) {
            Log::warning('PMB Google Sign-In: verifikasi gagal.', ['university_id' => $university->getKey(), 'reason' => $e->getMessage()]);

            return $this->frontend(['error' => 'google_failed']);
        }

        if (! $identity->emailVerified) {
            return $this->frontend(['error' => 'email_unverified']);
        }

        [$type, $account, $failure] = $this->resolve($identity);

        if ($failure !== null) {
            return $this->frontend(['error' => $failure]);
        }

        $ticket = Str::random(64);
        Cache::put($this->ticketKey($ticket), [
            'type' => $type,
            'university_id' => (string) $university->getKey(),
            'client_challenge' => (string) $flow['client_challenge'],
            'account_id' => $account?->getKey(),
            'identity' => ['sub' => $identity->subject, 'email' => $identity->email, 'name' => $identity->name, 'picture' => $identity->picture],
        ], $type === 'login' ? now()->addSeconds(self::LOGIN_TICKET_TTL_SECONDS) : now()->addMinutes(self::PENDING_TICKET_TTL_MINUTES));

        return $this->frontend(['ticket' => $ticket]);
    }

    // ------------------------------------------------------------------ 3. Tiket → sesi

    /**
     * Tiket login → token sesi. Tiket tautan/registrasi → info untuk langkah
     * berikutnya (tiket tetap berlaku sampai link()/register() berhasil).
     *
     * @return array<string, mixed>
     */
    public function resume(string $ticket, string $clientVerifier, string $deviceName, ?string $ip): array
    {
        $flow = $this->flow($ticket, $clientVerifier);
        $identity = $flow['identity'];

        if ($flow['type'] === 'link') {
            return ['status' => 'link_required', 'email' => $identity['email'], 'name' => $identity['name']];
        }

        if ($flow['type'] === 'register') {
            return ['status' => 'registration_required', 'email' => $identity['email'], 'name' => $identity['name'], 'avatar_url' => $identity['picture']];
        }

        Cache::forget($this->ticketKey($ticket));
        $account = ApplicantAccount::query()->whereKey($flow['account_id'])->where('google_id', $identity['sub'])->first();

        if ($account === null || $this->isBlocked($account)) {
            throw new DomainRuleException('Akun tidak aktif. Hubungi panitia PMB.', 'ACCOUNT_INACTIVE', 403);
        }

        if ($identity['picture'] !== null && $identity['picture'] !== $account->avatar_url) {
            $account->forceFill(['avatar_url' => $identity['picture']])->save();
        }

        return $this->authenticated($this->accounts->startSession($account, $deviceName, $ip, 'google'));
    }

    /**
     * Menautkan Google ke akun lama ber-email sama setelah sandi akun lama
     * benar. Salah sandi dihitung seperti login biasa (penguncian akun).
     *
     * @return array<string, mixed>
     */
    public function link(string $ticket, string $clientVerifier, string $password, string $deviceName, ?string $ip): array
    {
        $flow = $this->flow($ticket, $clientVerifier, 'link');
        $identity = $flow['identity'];
        $account = ApplicantAccount::query()->whereKey((string) $flow['account_id'])->first();

        $stillLinkable = $account !== null && $account->google_id === null && $account->email === $identity['email']
            && ! ApplicantAccount::query()->where('google_id', $identity['sub'])->exists();

        if (! $stillLinkable) {
            Cache::forget($this->ticketKey($ticket));

            throw new DomainRuleException('Akun tidak dapat dihubungkan. Silakan ulangi masuk dengan Google.', 'GOOGLE_LINK_UNAVAILABLE', 409);
        }

        $this->accounts->assertPassword($account, $password);
        Cache::forget($this->ticketKey($ticket));

        $session = DB::transaction(function () use ($account, $identity, $deviceName, $ip): array {
            $attributes = ['google_id' => $identity['sub'], 'google_linked_at' => now(), 'avatar_url' => $account->avatar_url ?? $identity['picture']];

            // Google memverifikasi email ini dan pemilik sandi membuktikan akun lama: orang yang sama.
            if ($account->email_verified_at === null) {
                $attributes['email_verified_at'] = now();

                if ($account->status === AccountStatus::Unverified) {
                    $attributes['status'] = AccountStatus::Active;
                }
            }

            $account->forceFill($attributes)->save();
            $this->audit->recordAction($account, AuditAction::LinkedAccount, ['event' => 'google_linked', 'ip' => $ip]);
            $this->outbox->notifyApplicant($account, 'pmb.account.security_alert', ['event_description' => 'akun Google dihubungkan untuk masuk ke portal']);

            return $this->accounts->startSession($account, $deviceName, $ip, 'google');
        });

        return $this->authenticated($session);
    }

    /**
     * Membuat akun baru dari identitas Google setelah pendaftar melengkapi
     * nama & persetujuan (FR-ACC-01). Email langsung terverifikasi.
     *
     * @param  array{name: string, phone?: string|null, info_source?: string|null, marketing_optional?: bool}  $data
     * @return array<string, mixed>
     */
    public function register(string $ticket, string $clientVerifier, array $data, string $deviceName, ?string $ip, ?string $userAgent): array
    {
        $flow = $this->flow($ticket, $clientVerifier, 'register');
        $identity = $flow['identity'];

        if (ApplicantAccount::query()->where(fn ($q) => $q->where('google_id', $identity['sub'])->orWhere('email', $identity['email']))->exists()) {
            Cache::forget($this->ticketKey($ticket));

            throw new DomainRuleException('Akun dengan email ini sudah terdaftar. Silakan ulangi masuk dengan Google.', 'ACCOUNT_EXISTS', 409);
        }

        $phone = ! empty($data['phone']) ? $this->accounts->normalizePhone((string) $data['phone']) : null;

        if ($phone !== null && ApplicantAccount::query()->where('phone', $phone)->exists()) {
            throw ValidationException::withMessages(['phone' => 'Nomor HP tidak dapat digunakan. Gunakan nomor lain atau hubungi panitia.']);
        }

        // Lead CRM yang masuk sebelum akun dibuat (sama seperti registrasi email).
        $lead = (array) Cache::pull('pmb:lead:'.sha1($identity['email']), []);
        $privacy = ContentService::activeVersion(ContentType::PrivacyPolicy);
        $terms = ContentService::activeVersion(ContentType::Terms);

        try {
            $account = DB::transaction(function () use ($data, $identity, $phone, $lead, $privacy, $terms, $ip, $userAgent): ApplicantAccount {
                $account = ApplicantAccount::query()->create([
                    'name' => trim((string) preg_replace('/\s+/', ' ', $data['name'])),
                    'email' => $identity['email'],
                    'phone' => $phone,
                    // Sandi acak yang tidak diketahui siapa pun; pendaftar dapat membuatnya lewat "Lupa kata sandi".
                    'password' => Str::password(40),
                    'has_password' => false,
                    'auth_provider' => 'google',
                    'google_id' => $identity['sub'],
                    'google_linked_at' => now(),
                    'avatar_url' => $identity['picture'],
                    'email_verified_at' => now(),
                    'status' => AccountStatus::Active,
                    'info_source' => $data['info_source'] ?? $lead['source'] ?? null,
                    'crm_lead_reference' => $lead['reference'] ?? null,
                ]);

                $consents = [[ConsentType::PrivacyPolicy, $privacy], [ConsentType::Terms, $terms]];

                if (! empty($data['marketing_optional'])) {
                    $consents[] = [ConsentType::MarketingOptional, null];
                }

                foreach ($consents as [$type, $content]) {
                    $this->accounts->recordConsent($account, $type, $content?->getKey(), $content?->version, $ip, $userAgent);
                }

                $this->audit->recordAction($account, AuditAction::Created, ['event' => 'google_registered', 'ip' => $ip]);
                $this->outbox->notifyApplicant($account, 'pmb.account.verified');

                return $account;
            });
        } catch (UniqueConstraintViolationException) {
            // Balapan: dua penyelesaian bersamaan untuk email/Google yang sama.
            throw new DomainRuleException('Akun dengan email ini sudah terdaftar. Silakan ulangi masuk dengan Google.', 'ACCOUNT_EXISTS', 409);
        }

        Cache::forget($this->ticketKey($ticket));

        return $this->authenticated($this->accounts->startSession($account, $deviceName, $ip, 'google'));
    }

    // ------------------------------------------------------------------ Helper

    /**
     * @return array{0: string|null, 1: ApplicantAccount|null, 2: string|null} [jenis tiket, akun, kode gagal]
     */
    private function resolve(GoogleIdentity $identity): array
    {
        $account = ApplicantAccount::query()->where('google_id', $identity->subject)->first();

        if ($account !== null) {
            return $this->isBlocked($account) ? [null, null, 'account_inactive'] : ['login', $account, null];
        }

        $account = ApplicantAccount::query()->where('email', $identity->email)->first();

        if ($account === null) {
            return ['register', null, null];
        }

        if ($this->isBlocked($account)) {
            return [null, null, 'account_inactive'];
        }

        // Email sama tetapi sudah tertaut ke akun Google lain (mis. email Google pernah berpindah pemilik).
        if ($account->google_id !== null) {
            return [null, null, 'account_conflict'];
        }

        return ['link', $account, null];
    }

    /** @return array<string, mixed> */
    private function flow(string $ticket, string $clientVerifier, ?string $type = null): array
    {
        $flow = Cache::get($this->ticketKey($ticket));

        $valid = is_array($flow)
            && hash_equals((string) $flow['client_challenge'], self::s256($clientVerifier))
            && (string) $flow['university_id'] === (string) $this->tenant->universityId()
            && ($type === null || $flow['type'] === $type);

        if (! $valid) {
            throw new DomainRuleException('Sesi masuk dengan Google tidak valid atau sudah kedaluwarsa. Silakan ulangi.', 'GOOGLE_SESSION_INVALID', 422);
        }

        return $flow;
    }

    private function isBlocked(ApplicantAccount $account): bool
    {
        return $account->anonymized_at !== null || in_array($account->status, [AccountStatus::Suspended, AccountStatus::Deactivated], true);
    }

    /**
     * @param  array{token: NewAccessToken, account: ApplicantAccount}  $session
     * @return array<string, mixed>
     */
    private function authenticated(array $session): array
    {
        return ['status' => 'authenticated', 'token' => $session['token'], 'account' => $session['account']];
    }

    /** @param  array<string, string>  $fragment */
    private function frontend(array $fragment): string
    {
        return rtrim((string) config('pmb.frontend_url'), '/').self::FRONTEND_CALLBACK_PATH.'#'.http_build_query($fragment);
    }

    private function stateKey(string $state): string
    {
        return 'pmb:google:state:'.hash('sha256', $state);
    }

    private function ticketKey(string $ticket): string
    {
        return 'pmb:google:ticket:'.hash('sha256', $ticket);
    }

    /** PKCE S256 (RFC 7636 §4.2): BASE64URL(SHA256(verifier)). */
    public static function s256(string $verifier): string
    {
        return rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
    }
}
