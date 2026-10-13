<?php

namespace Modules\Admission\Services\Account;

use App\Models\User;
use App\Support\Http\Exceptions\ConflictException;
use App\Support\Tenancy\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\NewAccessToken;
use Laravel\Sanctum\PersonalAccessToken;
use Modules\Admission\Enums\AccountStatus;
use Modules\Admission\Enums\ConsentType;
use Modules\Admission\Enums\ContentType;
use Modules\Admission\Enums\FormStatus;
use Modules\Admission\Enums\RecoveryRequestStatus;
use Modules\Admission\Exceptions\DomainRuleException;
use Modules\Admission\Models\AccountConsent;
use Modules\Admission\Models\AccountRecoveryRequest;
use Modules\Admission\Models\ApplicantAccount;
use Modules\Admission\Models\Application;
use Modules\Admission\Models\OtpChallenge;
use Modules\Admission\Outbox\OutboxRecorder;
use Modules\Admission\Services\Configuration\ContentService;
use Modules\Admission\Support\IdentityHasher;
use Modules\Admission\Support\PmbSettings;
use Modules\AuditLog\Enums\AuditAction;
use Modules\AuditLog\Services\AuditLogService;

/**
 * Registrasi & akun calon mahasiswa (docs/pmb/04 §C, SM-01, 11 §A.3).
 * Pesan & respons dibuat seragam untuk mencegah enumerasi akun.
 */
class AccountService
{
    private const VERIFY_TTL_HOURS = 24;

    private const RESET_TTL_MINUTES = 60;

    private const OTP_TTL_MINUTES = 5;

    private const OTP_MAX_ATTEMPTS = 5;

    private const LOGIN_MAX_FAILURES = 5;

    private const LOGIN_WINDOW_SECONDS = 900;

    public function __construct(
        private readonly TenantContext $tenant,
        private readonly OutboxRecorder $outbox,
        private readonly PmbSettings $settings,
        private readonly AuditLogService $audit,
    ) {}

    // ------------------------------------------------------------------ Registrasi

    /**
     * FR-ACC-01. Email sudah terdaftar → TIDAK membuat akun, mengirim email
     * `registration_attempt` ke pemilik; pemanggil mengembalikan respons
     * identik (AC-01 "Email sudah ada").
     *
     * @param  array<string, mixed>  $data
     * @return ApplicantAccount|null null bila email sudah terdaftar
     */
    public function register(array $data, ?string $ip, ?string $userAgent, ?User $assistedBy = null): ?ApplicantAccount
    {
        $email = $this->normalizeEmail((string) $data['email']);
        $phone = isset($data['phone']) && $data['phone'] !== '' ? $this->normalizePhone((string) $data['phone']) : null;
        $existing = ApplicantAccount::query()->where('email', $email)->first();

        if ($existing !== null) {
            if ($assistedBy !== null) {
                throw new ConflictException('Email sudah terdaftar sebagai akun pendaftar.', 'ACCOUNT_EXISTS');
            }

            $this->outbox->notifyApplicant($existing, 'pmb.account.registration_attempt');

            return null;
        }

        if ($phone !== null && ApplicantAccount::query()->where('phone', $phone)->exists()) {
            // Nomor HP dipakai untuk login (BR-07) — pesan generik agar tidak membocorkan pemiliknya.
            throw ValidationException::withMessages(['phone' => 'Nomor HP tidak dapat digunakan. Gunakan nomor lain atau hubungi panitia.']);
        }

        $privacy = ContentService::activeVersion(ContentType::PrivacyPolicy);
        $terms = ContentService::activeVersion(ContentType::Terms);

        return DB::transaction(function () use ($data, $email, $phone, $ip, $userAgent, $assistedBy, $privacy, $terms): ApplicantAccount {
            $account = ApplicantAccount::query()->create([
                'name' => trim(preg_replace('/\s+/', ' ', (string) $data['name'])),
                'email' => $email,
                'phone' => $phone,
                'password' => $assistedBy !== null ? Str::password(24) : (string) $data['password'],
                'status' => AccountStatus::Unverified,
                'info_source' => $data['info_source'] ?? null,
                'created_by_user_id' => $assistedBy?->getKey(),
                'crm_lead_reference' => $data['crm_lead_reference'] ?? null,
            ]);

            $consents = [
                [ConsentType::PrivacyPolicy, $privacy],
                [ConsentType::Terms, $terms],
            ];

            if (! empty($data['marketing_optional'])) {
                $consents[] = [ConsentType::MarketingOptional, null];
            }

            foreach ($consents as [$type, $content]) {
                $this->recordConsent($account, $type, $content?->getKey(), $content?->version, $ip, $userAgent);
            }

            $this->outbox->notifyApplicant($account, 'pmb.account.registered');
            $this->sendVerification($account, assisted: $assistedBy !== null);

            return $account;
        });
    }

    public function sendVerification(ApplicantAccount $account, bool $assisted = false): void
    {
        if ($account->isVerified() && ! $assisted) {
            return;
        }

        $channel = (string) $this->settings->get('pmb.otp_channel');

        if (in_array($channel, ['sms', 'whatsapp'], true) && $account->phone) {
            $this->issueOtp($account, $channel, $account->phone, 'verify_phone');

            return;
        }

        $token = $this->issueToken($account, 'email', $account->email, $assisted ? 'reset_password' : 'verify_email', now()->addHours(self::VERIFY_TTL_HOURS));
        $base = rtrim((string) config('pmb.frontend_url'), '/');

        if ($assisted) {
            // Pendaftaran loket: pendaftar menetapkan sandinya sendiri lewat tautan
            // (sekaligus memverifikasi email) — petugas tidak pernah tahu sandinya.
            $this->outbox->notifyApplicant($account, 'pmb.account.verification_requested', [
                'verification_url' => $base.'/pmb/atur-sandi?token='.$token.'&verify=1',
            ]);

            return;
        }

        $this->outbox->notifyApplicant($account, 'pmb.account.verification_requested', [
            'verification_url' => $base.'/pmb/verifikasi?token='.$token,
        ]);
    }

    public function verifyEmail(string $token): ApplicantAccount
    {
        $challenge = $this->consumeToken($token, ['verify_email']);
        $account = ApplicantAccount::query()->findOrFail($challenge->applicant_account_id);

        return $this->markVerified($account, 'email');
    }

    public function verifyOtp(ApplicantAccount $account, string $purpose, string $code): ApplicantAccount
    {
        $challenge = OtpChallenge::query()->where('applicant_account_id', $account->getKey())->where('purpose', $purpose)
            ->whereNull('consumed_at')->latest()->first();

        $this->checkOtp($challenge, $code);

        return $this->markVerified($account, $purpose === 'verify_phone' ? 'phone' : 'email');
    }

    // ------------------------------------------------------------------ Login & sesi

    /**
     * FR-ACC-03 & FR-ACC-08: kredensial salah → pesan generik; 5 gagal/15
     * menit → `locked` (durasi eksponensial 15, 30, 60 … menit, maks 24 jam).
     *
     * @return array{token: NewAccessToken, account: ApplicantAccount}
     */
    public function login(string $login, string $password, string $deviceName, ?string $ip): array
    {
        $login = trim($login);
        $account = str_contains($login, '@')
            ? ApplicantAccount::query()->where('email', $this->normalizeEmail($login))->first()
            : ApplicantAccount::query()->where('phone', $this->normalizePhone($login))->first();

        $invalid = new DomainRuleException('Email/nomor HP atau kata sandi salah.', 'INVALID_CREDENTIALS', 401);

        if ($account === null) {
            Hash::check($password, '$2y$12$'.str_repeat('a', 53)); // waktu respons seragam

            throw $invalid;
        }

        if ($account->status === AccountStatus::Locked && $account->locked_until !== null && $account->locked_until->isFuture()) {
            throw new DomainRuleException('Akun terkunci sementara. Coba lagi setelah '.$account->locked_until->setTimezone($this->timezone())->format('H:i').' atau atur ulang kata sandi.', 'ACCOUNT_LOCKED', 423, ['locked_until' => $account->locked_until->toIso8601String()]);
        }

        if (in_array($account->status, [AccountStatus::Suspended, AccountStatus::Deactivated], true)) {
            throw $invalid;
        }

        if (! Hash::check($password, $account->password)) {
            $this->registerFailedLogin($account);

            throw $invalid;
        }

        return DB::transaction(function () use ($account, $deviceName, $ip): array {
            RateLimiter::clear($this->loginKey($account));

            $wasLocked = $account->status === AccountStatus::Locked;
            $account->forceFill([
                'status' => $wasLocked ? ($account->isVerified() ? AccountStatus::Active : AccountStatus::Unverified) : $account->status,
                'failed_login_count' => 0,
                'locked_until' => null,
                'last_login_at' => now(),
            ])->save();

            // Maksimal 5 token aktif: token tertua dicabut (FR-ACC-03).
            $max = (int) config('pmb.applicant_max_tokens', 5);
            $tokens = $account->tokens()->orderByDesc('created_at')->get();

            foreach ($tokens->slice($max - 1) as $old) {
                $old->delete();
            }

            $token = $account->createToken(mb_substr($deviceName ?: 'browser', 0, 100), [ApplicantAccount::TOKEN_ABILITY], now()->addHours((int) config('pmb.applicant_token_ttl_hours', 12)));

            $this->audit->recordAction($account, AuditAction::Updated, ['event' => 'applicant_login', 'ip' => $ip]);

            return ['token' => $token, 'account' => $account->refresh()];
        });
    }

    public function logout(ApplicantAccount $account): void
    {
        $token = $account->currentAccessToken();

        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }
    }

    public function logoutAll(ApplicantAccount $account): void
    {
        $account->tokens()->delete();
    }

    /** @return array<int, array<string, mixed>> */
    public function sessions(ApplicantAccount $account): array
    {
        $current = $account->currentAccessToken();

        return $account->tokens()->orderByDesc('last_used_at')->get()->map(fn (PersonalAccessToken $token) => [
            'id' => $token->getKey(),
            'device_name' => $token->name,
            'last_used_at' => $token->last_used_at?->toIso8601String(),
            'created_at' => $token->created_at?->toIso8601String(),
            'expires_at' => $token->expires_at?->toIso8601String(),
            'current' => $current instanceof PersonalAccessToken && $current->getKey() === $token->getKey(),
        ])->all();
    }

    public function revokeSession(ApplicantAccount $account, string $tokenId): void
    {
        $account->tokens()->whereKey($tokenId)->firstOrFail()->delete();
    }

    // ------------------------------------------------------------------ Sandi

    /** FR-ACC-04 — respons selalu sama (anti-enumerasi). */
    public function forgotPassword(string $login): void
    {
        $login = trim($login);
        $account = str_contains($login, '@')
            ? ApplicantAccount::query()->where('email', $this->normalizeEmail($login))->first()
            : ApplicantAccount::query()->where('phone', $this->normalizePhone($login))->first();

        if ($account === null || $account->status === AccountStatus::Deactivated) {
            return;
        }

        if (! str_contains($login, '@') && $account->phone) {
            $this->issueOtp($account, (string) $this->settings->get('pmb.otp_channel') === 'whatsapp' ? 'whatsapp' : 'sms', $account->phone, 'reset_password');

            return;
        }

        $token = $this->issueToken($account, 'email', $account->email, 'reset_password', now()->addMinutes(self::RESET_TTL_MINUTES));
        $this->outbox->notifyApplicant($account, 'pmb.account.password_reset', [
            'reset_url' => rtrim((string) config('pmb.frontend_url'), '/').'/pmb/atur-sandi?token='.$token,
        ]);
    }

    /**
     * Reset via token tautan; `verify` = token pendaftaran loket (sekaligus
     * memverifikasi email). Mencabut seluruh token aktif.
     */
    public function resetPassword(string $token, string $password, bool $alsoVerify = false): ApplicantAccount
    {
        $challenge = $this->consumeToken($token, ['reset_password']);
        $account = ApplicantAccount::query()->findOrFail($challenge->applicant_account_id);

        return $this->applyNewPassword($account, $password, $alsoVerify);
    }

    public function resetPasswordWithOtp(string $phone, string $code, string $password): ApplicantAccount
    {
        $account = ApplicantAccount::query()->where('phone', $this->normalizePhone($phone))->first();
        $challenge = $account ? OtpChallenge::query()->where('applicant_account_id', $account->getKey())->where('purpose', 'reset_password')->whereNull('consumed_at')->latest()->first() : null;

        $this->checkOtp($challenge, $code);

        return $this->applyNewPassword($account, $password, false);
    }

    public function changePassword(ApplicantAccount $account, string $current, string $new): void
    {
        if (! Hash::check($current, $account->password)) {
            throw ValidationException::withMessages(['current_password' => 'Kata sandi saat ini salah.']);
        }

        $account->forceFill(['password' => $new])->save();
        $currentToken = $account->currentAccessToken();
        $account->tokens()->when($currentToken instanceof PersonalAccessToken, fn ($q) => $q->whereKeyNot($currentToken->getKey()))->delete();
        $this->outbox->notifyApplicant($account, 'pmb.account.security_alert', ['event_description' => 'kata sandi diubah']);
    }

    // ------------------------------------------------------------------ Profil akun

    /** @param  array<string, mixed>  $data */
    public function updateAccount(ApplicantAccount $account, array $data): ApplicantAccount
    {
        $values = [];

        if (isset($data['name'])) {
            $values['name'] = trim(preg_replace('/\s+/', ' ', (string) $data['name']));
        }

        if (array_key_exists('phone', $data)) {
            $phone = $data['phone'] ? $this->normalizePhone((string) $data['phone']) : null;

            if ($phone !== null && ApplicantAccount::query()->where('phone', $phone)->whereKeyNot($account->getKey())->exists()) {
                throw ValidationException::withMessages(['phone' => 'Nomor HP tidak dapat digunakan.']);
            }

            $values['phone'] = $phone;

            if ($phone !== $account->phone) {
                $values['phone_verified_at'] = null;
            }
        }

        $account->forceFill($values)->save();

        return $account->refresh();
    }

    /** FR-ACC-05: verifikasi ke email baru, notifikasi ke email lama. */
    public function requestEmailChange(ApplicantAccount $account, string $newEmail, string $password): void
    {
        if (! Hash::check($password, $account->password)) {
            throw ValidationException::withMessages(['password' => 'Kata sandi salah.']);
        }

        $newEmail = $this->normalizeEmail($newEmail);

        if (ApplicantAccount::query()->where('email', $newEmail)->exists()) {
            // Respons tetap sukses: keberadaan email lain tidak diungkap.
            return;
        }

        $token = $this->issueToken($account, 'email', $newEmail, 'change_email', now()->addHours(self::VERIFY_TTL_HOURS), ['new_email' => $newEmail]);
        $this->outbox->record(OutboxRecorder::NOTIFY_APPLICANT, $account, [
            'account_id' => $account->getKey(),
            'event_key' => 'pmb.account.verification_requested',
            'placeholders' => ['verification_url' => rtrim((string) config('pmb.frontend_url'), '/').'/pmb/verifikasi?token='.$token.'&type=email'],
            'data' => [],
            'override_email' => $newEmail,
        ]);
    }

    public function confirmEmailChange(string $token): ApplicantAccount
    {
        $challenge = $this->consumeToken($token, ['change_email']);
        $account = ApplicantAccount::query()->findOrFail($challenge->applicant_account_id);
        $newEmail = (string) ($challenge->payload['new_email'] ?? '');

        if ($newEmail === '' || ApplicantAccount::query()->where('email', $newEmail)->whereKeyNot($account->getKey())->exists()) {
            throw new DomainRuleException('Email baru tidak dapat dipakai.', 'EMAIL_UNAVAILABLE', 409);
        }

        return DB::transaction(function () use ($account, $newEmail): ApplicantAccount {
            $this->outbox->notifyApplicant($account, 'pmb.account.security_alert', ['event_description' => 'email akun diubah']);
            $account->forceFill(['email' => $newEmail, 'email_verified_at' => now()])->save();
            $this->audit->recordAction($account, AuditAction::Updated, ['event' => 'email_changed']);

            return $account->refresh();
        });
    }

    /** Hapus akun (POR-12) — ditolak bila ada aplikasi non-terminal. */
    public function deactivateSelf(ApplicantAccount $account, string $password): void
    {
        if (! Hash::check($password, $account->password)) {
            throw ValidationException::withMessages(['password' => 'Kata sandi salah.']);
        }

        $nonTerminal = Application::query()->where('applicant_account_id', $account->getKey())
            ->whereNotIn('form_status', [FormStatus::Void->value, FormStatus::Withdrawn->value])
            ->where(fn ($q) => $q->whereNull('submitted_at')->orWhereDoesntHave('reRegistration', fn ($r) => $r->whereIn('status', ['enrolled', 'declined', 'expired', 'cancelled'])))
            ->exists();

        if ($nonTerminal) {
            throw new ConflictException('Akun tidak dapat dihapus karena masih ada pendaftaran yang berjalan.', 'ACCOUNT_IN_USE');
        }

        DB::transaction(function () use ($account): void {
            $account->forceFill(['status' => AccountStatus::Deactivated, 'deactivated_at' => now()])->save();
            $account->tokens()->delete();
            $this->outbox->notifyApplicant($account, 'pmb.account.deactivated');
        });
    }

    public function setSuspended(ApplicantAccount $account, bool $suspended, string $reason, User $actor): ApplicantAccount
    {
        $target = $suspended ? AccountStatus::Suspended : ($account->isVerified() ? AccountStatus::Active : AccountStatus::Unverified);

        if ($suspended && ! $account->status->canTransitionTo(AccountStatus::Suspended)) {
            throw new ConflictException('Status akun tidak dapat dibekukan.', 'INVALID_TRANSITION');
        }

        return DB::transaction(function () use ($account, $target, $suspended, $reason): ApplicantAccount {
            $old = $account->status->value;
            $account->forceFill(['status' => $target, 'suspension_reason' => $suspended ? $reason : null])->save();

            if ($suspended) {
                $account->tokens()->delete();
                $this->outbox->notifyApplicant($account, 'pmb.account.suspended');
            }

            $this->audit->record($account, AuditAction::Updated, ['status' => $old], ['status' => $target->value], $reason);

            return $account->refresh();
        });
    }

    // ------------------------------------------------------------------ Pemulihan akun

    /** @param  array<string, mixed>  $data */
    public function submitRecoveryRequest(array $data, ?string $identityFileId): AccountRecoveryRequest
    {
        $universityId = (string) $this->tenant->universityId();
        $identityHash = IdentityHasher::forNationalId($universityId, (string) $data['identity_number']);

        $account = ApplicantAccount::query()->whereHas('profile', fn ($q) => $q->where('identity_hash', $identityHash))->first();

        return AccountRecoveryRequest::query()->create([
            'applicant_account_id' => $account?->getKey(),
            'full_name' => $data['full_name'],
            'birth_date' => $data['birth_date'],
            'identity_hash' => $identityHash,
            'new_email' => $this->normalizeEmail((string) $data['new_email']),
            'identity_file_id' => $identityFileId,
            'status' => RecoveryRequestStatus::Pending,
        ]);
    }

    public function resolveRecoveryRequest(AccountRecoveryRequest $request, bool $approve, string $note, User $actor): AccountRecoveryRequest
    {
        if ($request->status !== RecoveryRequestStatus::Pending) {
            throw new ConflictException('Permintaan sudah diproses.', 'ALREADY_DECIDED');
        }

        return DB::transaction(function () use ($request, $approve, $note, $actor): AccountRecoveryRequest {
            if ($approve) {
                $account = $request->account ?? throw new ConflictException('Tidak ada akun dengan identitas tersebut.', 'ACCOUNT_NOT_FOUND');

                if (ApplicantAccount::query()->where('email', $request->new_email)->whereKeyNot($account->getKey())->exists()) {
                    throw new ConflictException('Email baru sudah dipakai akun lain.', 'EMAIL_UNAVAILABLE');
                }

                $old = $account->email;
                $account->forceFill(['email' => $request->new_email, 'email_verified_at' => null])->save();
                $account->tokens()->delete();
                $this->audit->record($account, AuditAction::Updated, ['email' => $old], ['email' => $request->new_email], 'Pemulihan akun: '.$note);
                $this->forgotPassword($request->new_email);
            }

            $request->forceFill([
                'status' => $approve ? RecoveryRequestStatus::Approved : RecoveryRequestStatus::Rejected,
                'resolution_note' => $note,
                'resolved_by' => $actor->getKey(),
                'resolved_at' => now(),
            ])->save();

            return $request->refresh();
        });
    }

    // ------------------------------------------------------------------ Consent

    public function recordConsent(ApplicantAccount $account, ConsentType $type, ?string $contentId, ?int $version, ?string $ip, ?string $userAgent, ?string $applicationId = null): AccountConsent
    {
        return AccountConsent::query()->create([
            'university_id' => $account->university_id,
            'applicant_account_id' => $account->getKey(),
            'application_id' => $applicationId,
            'consent_type' => $type->value,
            'content_id' => $contentId,
            'document_version' => $version,
            'granted_at' => now(),
            'ip_address' => $ip,
            'user_agent' => $userAgent ? mb_substr($userAgent, 0, 500) : null,
        ]);
    }

    public function setMarketingConsent(ApplicantAccount $account, bool $granted, ?string $ip, ?string $ua): void
    {
        $active = AccountConsent::query()->where('applicant_account_id', $account->getKey())
            ->where('consent_type', ConsentType::MarketingOptional->value)->whereNull('revoked_at')->first();

        if ($granted && $active === null) {
            $this->recordConsent($account, ConsentType::MarketingOptional, null, null, $ip, $ua);
        } elseif (! $granted && $active !== null) {
            $active->forceFill(['revoked_at' => now()])->save();
        }
    }

    // ------------------------------------------------------------------ Helper

    public function normalizeEmail(string $email): string
    {
        return mb_strtolower(trim($email));
    }

    /** E.164; nomor lokal 08xx → +628xx (default +62, FR-ACC-01). */
    public function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/[^\d+]/', '', trim($phone)) ?? '';

        if (str_starts_with($digits, '+')) {
            return $digits;
        }

        if (str_starts_with($digits, '0')) {
            return '+62'.substr($digits, 1);
        }

        if (str_starts_with($digits, '62')) {
            return '+'.$digits;
        }

        return '+'.$digits;
    }

    private function markVerified(ApplicantAccount $account, string $via): ApplicantAccount
    {
        return DB::transaction(function () use ($account, $via): ApplicantAccount {
            $attributes = $via === 'phone' ? ['phone_verified_at' => now()] : ['email_verified_at' => now()];

            if ($account->status === AccountStatus::Unverified) {
                $attributes['status'] = AccountStatus::Active;
            }

            $account->forceFill($attributes)->save();
            $this->outbox->notifyApplicant($account, 'pmb.account.verified');

            return $account->refresh();
        });
    }

    private function applyNewPassword(ApplicantAccount $account, string $password, bool $alsoVerify): ApplicantAccount
    {
        return DB::transaction(function () use ($account, $password, $alsoVerify): ApplicantAccount {
            $attributes = ['password' => $password, 'failed_login_count' => 0, 'locked_until' => null];

            if ($account->status === AccountStatus::Locked) {
                $attributes['status'] = $account->isVerified() || $alsoVerify ? AccountStatus::Active : AccountStatus::Unverified;
            }

            if ($alsoVerify && $account->email_verified_at === null) {
                $attributes['email_verified_at'] = now();

                if ($account->status === AccountStatus::Unverified) {
                    $attributes['status'] = AccountStatus::Active;
                }
            }

            $account->forceFill($attributes)->save();
            $account->tokens()->delete();
            RateLimiter::clear($this->loginKey($account));
            $this->outbox->notifyApplicant($account, 'pmb.account.security_alert', ['event_description' => 'kata sandi diatur ulang']);

            return $account->refresh();
        });
    }

    private function registerFailedLogin(ApplicantAccount $account): void
    {
        $key = $this->loginKey($account);
        RateLimiter::hit($key, self::LOGIN_WINDOW_SECONDS);
        $account->forceFill(['failed_login_count' => $account->failed_login_count + 1])->save();

        if (RateLimiter::attempts($key) >= self::LOGIN_MAX_FAILURES && $account->status->canTransitionTo(AccountStatus::Locked)) {
            $minutes = min(15 * (2 ** $account->lockout_count), 1440);
            $account->forceFill([
                'status' => AccountStatus::Locked,
                'locked_until' => now()->addMinutes($minutes),
                'lockout_count' => min($account->lockout_count + 1, 10),
            ])->save();
            $account->tokens()->delete();
            RateLimiter::clear($key);
            $this->outbox->notifyApplicant($account, 'pmb.account.locked', ['locked_until' => $account->locked_until->setTimezone($this->timezone())->format('d-m-Y H:i')]);
        } elseif ($account->status === AccountStatus::Locked && $account->locked_until?->isPast()) {
            $account->forceFill(['status' => $account->isVerified() ? AccountStatus::Active : AccountStatus::Unverified])->save();
        }
    }

    private function loginKey(ApplicantAccount $account): string
    {
        return 'pmb-login-fail:'.$account->getKey();
    }

    /**
     * Token tautan sekali pakai: "{challenge_id}.{secret}" — hanya hash rahasia disimpan.
     *
     * @param  array<string, mixed>  $payload
     */
    private function issueToken(ApplicantAccount $account, string $channel, string $destination, string $purpose, \DateTimeInterface $expiresAt, array $payload = []): string
    {
        OtpChallenge::query()->where('applicant_account_id', $account->getKey())->where('purpose', $purpose)->whereNull('consumed_at')
            ->update(['consumed_at' => now()]);

        $secret = Str::random(48);
        $challenge = OtpChallenge::query()->create([
            'university_id' => $account->university_id,
            'applicant_account_id' => $account->getKey(),
            'channel' => $channel,
            'destination_hash' => IdentityHasher::forDestination((string) $account->university_id, $destination),
            'purpose' => $purpose,
            'code_hash' => hash('sha256', $secret),
            'payload' => $payload ?: null,
            'expires_at' => $expiresAt,
            'attempts' => 0,
        ]);

        return $challenge->getKey().'.'.$secret;
    }

    /** @param  array<int, string>  $purposes */
    private function consumeToken(string $token, array $purposes): OtpChallenge
    {
        [$id, $secret] = array_pad(explode('.', $token, 2), 2, '');
        $challenge = $id !== '' ? OtpChallenge::query()->whereKey($id)->whereIn('purpose', $purposes)->first() : null;

        if ($challenge === null || $challenge->consumed_at !== null || ! hash_equals($challenge->code_hash, hash('sha256', $secret))) {
            throw new DomainRuleException('Tautan tidak valid atau sudah digunakan.', 'INVALID_TOKEN', 422);
        }

        if ($challenge->expires_at->isPast()) {
            throw new DomainRuleException('Tautan sudah kedaluwarsa. Minta tautan baru.', 'TOKEN_EXPIRED', 410);
        }

        $challenge->forceFill(['consumed_at' => now()])->save();

        return $challenge;
    }

    /**
     * OTP 6 digit: berlaku 5 menit, maks 5 percobaan, kirim ulang setelah 60
     * detik, maks 5 kirim/jam/tujuan (FR-ACC-02).
     */
    private function issueOtp(ApplicantAccount $account, string $channel, string $destination, string $purpose): void
    {
        $destinationHash = IdentityHasher::forDestination((string) $account->university_id, $destination);
        $recent = OtpChallenge::query()->where('destination_hash', $destinationHash)->where('created_at', '>=', now()->subHour())->get();

        if ($recent->count() >= 5) {
            throw new DomainRuleException('Terlalu banyak permintaan kode. Coba lagi nanti.', 'OTP_RATE_LIMIT', 429);
        }

        if ($recent->contains(fn ($c) => $c->created_at->gt(now()->subSeconds(60)))) {
            throw new DomainRuleException('Tunggu 60 detik sebelum meminta kode baru.', 'OTP_RATE_LIMIT', 429);
        }

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        OtpChallenge::query()->where('applicant_account_id', $account->getKey())->where('purpose', $purpose)->whereNull('consumed_at')->update(['consumed_at' => now()]);
        OtpChallenge::query()->create([
            'university_id' => $account->university_id,
            'applicant_account_id' => $account->getKey(),
            'channel' => $channel,
            'destination_hash' => $destinationHash,
            'purpose' => $purpose,
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes(self::OTP_TTL_MINUTES),
            'attempts' => 0,
        ]);

        $this->outbox->record(OutboxRecorder::NOTIFY_APPLICANT, $account, [
            'account_id' => $account->getKey(),
            'event_key' => $purpose === 'reset_password' ? 'pmb.account.password_reset' : 'pmb.account.verification_requested',
            'placeholders' => ['otp_code' => $code],
            'data' => [],
            'channels' => ['sms'],
            'force_sms' => true,
        ]);
    }

    private function checkOtp(?OtpChallenge $challenge, string $code): void
    {
        if ($challenge === null || $challenge->expires_at->isPast()) {
            throw new DomainRuleException('Kode tidak valid atau kedaluwarsa.', 'INVALID_OTP', 422);
        }

        if ($challenge->attempts >= self::OTP_MAX_ATTEMPTS) {
            throw new DomainRuleException('Percobaan kode habis. Minta kode baru.', 'OTP_ATTEMPTS_EXCEEDED', 423);
        }

        if (! Hash::check($code, $challenge->code_hash)) {
            $challenge->increment('attempts');

            throw new DomainRuleException('Kode tidak valid atau kedaluwarsa.', 'INVALID_OTP', 422);
        }

        $challenge->forceFill(['consumed_at' => now()])->save();
    }

    private function timezone(): string
    {
        return (string) (\Modules\Tenancy\Models\University::query()->whereKey($this->tenant->universityId())->value('timezone') ?: config('app.timezone'));
    }
}
