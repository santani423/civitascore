<?php

namespace Modules\Admission\Controllers\Applicant;

use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Modules\Admission\Contracts\CaptchaVerifier;
use Modules\Admission\Exceptions\DomainRuleException;
use Modules\Admission\Models\ApplicantAccount;
use Modules\Admission\Services\Account\AccountService;
use Modules\Admission\Support\CoreFormFields;
use Modules\Admission\Support\PmbFileStorage;

/** Autentikasi pendaftar (docs/pmb/11 §A.3, 04 §C). */
class AuthController extends ApplicantController
{
    public function __construct(private readonly AccountService $accounts, private readonly CaptchaVerifier $captcha) {}

    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'min:3', 'max:150'],
            'email' => ['required', 'email:rfc', 'max:191'],
            'phone' => ['nullable', 'string', 'regex:/^\+?[0-9\s\-]{8,20}$/'],
            'password' => ['required', 'confirmed', $this->passwordRule()],
            'consents' => ['required', 'array'],
            'consents.privacy_policy' => ['accepted'],
            'consents.terms' => ['accepted'],
            'consents.marketing_optional' => ['nullable', 'boolean'],
            'info_source' => ['nullable', 'in:'.implode(',', CoreFormFields::INFO_SOURCES)],
            'captcha_token' => ['nullable', 'string', 'max:4096'],
        ]);
        $this->assertCaptcha($request, $data['captcha_token'] ?? null);

        $this->accounts->register([...$data, 'marketing_optional' => (bool) ($data['consents']['marketing_optional'] ?? false)], $request->ip(), $request->userAgent());

        // Respons identik untuk email baru maupun terdaftar (anti-enumerasi, AC-01).
        return ApiResponse::success(['status' => 'verification_sent'], 'Akun dibuat. Periksa email Anda untuk verifikasi.', status: 201);
    }

    public function verifyEmail(Request $request): JsonResponse
    {
        $account = $this->accounts->verifyEmail((string) $request->validate(['token' => ['required', 'string', 'max:200']])['token']);

        return ApiResponse::success(['status' => $account->status->value], 'Email berhasil diverifikasi.');
    }

    public function resendVerification(Request $request): JsonResponse
    {
        $this->accounts->sendVerification($this->account($request));

        return ApiResponse::success(null, 'Tautan verifikasi dikirim ulang.', status: 202);
    }

    public function sendOtp(Request $request): JsonResponse
    {
        $data = $request->validate(['purpose' => ['required', 'in:verify_phone,reset_password'], 'destination' => ['nullable', 'string', 'max:191']]);

        if ($data['purpose'] === 'reset_password') {
            $this->accounts->forgotPassword((string) ($data['destination'] ?? ''));
        } elseif ($request->user() instanceof ApplicantAccount) {
            $this->accounts->sendVerification($request->user());
        }

        return ApiResponse::success(null, 'Jika data terdaftar, kode telah dikirim.', status: 202);
    }

    public function verifyOtp(Request $request): JsonResponse
    {
        $data = $request->validate([
            'purpose' => ['required', 'in:verify_phone,reset_password'],
            'destination' => ['required_if:purpose,reset_password', 'nullable', 'string'],
            'code' => ['required', 'digits:6'],
            'password' => ['required_if:purpose,reset_password', 'nullable', 'confirmed', $this->passwordRule()],
        ]);

        if ($data['purpose'] === 'reset_password') {
            $this->accounts->resetPasswordWithOtp((string) $data['destination'], $data['code'], (string) $data['password']);

            return ApiResponse::success(null, 'Kata sandi berhasil diatur ulang.');
        }

        $account = $request->user();
        abort_unless($account instanceof ApplicantAccount, 401);
        $this->accounts->verifyOtp($account, 'verify_phone', $data['code']);

        return ApiResponse::success(null, 'Nomor HP terverifikasi.');
    }

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'login' => ['required', 'string', 'max:191'],
            'password' => ['required', 'string', 'max:200'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ]);

        $result = $this->accounts->login($data['login'], $data['password'], (string) ($data['device_name'] ?? $request->userAgent() ?? 'browser'), $request->ip());

        return ApiResponse::success([
            'token' => $result['token']->plainTextToken,
            'expires_at' => $result['token']->accessToken->expires_at?->toIso8601String(),
            'account' => $this->presentAccount($result['account']),
        ], 'Berhasil masuk.');
    }

    public function logout(Request $request): JsonResponse
    {
        $this->accounts->logout($this->account($request));

        return response()->json(null, 204);
    }

    public function logoutAll(Request $request): JsonResponse
    {
        $this->accounts->logoutAll($this->account($request));

        return response()->json(null, 204);
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $data = $request->validate(['email' => ['required_without:login', 'nullable', 'string', 'max:191'], 'login' => ['nullable', 'string', 'max:191'], 'captcha_token' => ['nullable', 'string']]);
        $this->assertCaptcha($request, $data['captcha_token'] ?? null);
        $this->accounts->forgotPassword((string) ($data['login'] ?? $data['email']));

        return ApiResponse::success(null, 'Jika akun terdaftar, instruksi telah dikirim.', status: 202);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string', 'max:200'],
            'password' => ['required', 'confirmed', $this->passwordRule()],
            'verify' => ['nullable', 'boolean'],
        ]);
        $this->accounts->resetPassword($data['token'], $data['password'], (bool) ($data['verify'] ?? false));

        return ApiResponse::success(null, 'Kata sandi berhasil diatur. Silakan masuk.');
    }

    public function confirmEmailChange(Request $request): JsonResponse
    {
        $this->accounts->confirmEmailChange((string) $request->validate(['token' => ['required', 'string', 'max:200']])['token']);

        return ApiResponse::success(null, 'Email berhasil diganti.');
    }

    public function sessions(Request $request): JsonResponse
    {
        return ApiResponse::success($this->accounts->sessions($this->account($request)));
    }

    public function revokeSession(Request $request, string $tokenId): JsonResponse
    {
        $this->accounts->revokeSession($this->account($request), $tokenId);

        return response()->json(null, 204);
    }

    public function recoveryRequest(Request $request, PmbFileStorage $storage): JsonResponse
    {
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:150'],
            'birth_date' => ['required', 'date'],
            'identity_number' => ['required', 'string', 'max:30'],
            'new_email' => ['required', 'email', 'max:191'],
            'identity_document' => ['required', 'file', 'max:2048'],
            'captcha_token' => ['nullable', 'string'],
        ]);
        $this->assertCaptcha($request, $data['captcha_token'] ?? null);
        $file = $storage->store($request->file('identity_document'), ['image/jpeg', 'image/png', 'application/pdf'], 2048, ['recovery']);
        $this->accounts->submitRecoveryRequest($data, (string) $file->getKey());

        return ApiResponse::success(null, 'Permintaan diterima dan akan diproses panitia maksimal 2 hari kerja.', status: 202);
    }

    /** @return array<string, mixed> */
    public static function presentAccount(ApplicantAccount $account): array
    {
        return [
            'id' => $account->getKey(),
            'name' => $account->name,
            'email' => $account->email,
            'phone' => $account->phone,
            'status' => $account->status->value,
            'email_verified' => $account->email_verified_at !== null,
            'phone_verified' => $account->phone_verified_at !== null,
            'verified' => $account->isVerified(),
        ];
    }

    private function passwordRule(): Password
    {
        $rule = Password::min(8)->letters()->numbers();

        // Cek sandi bocor (HIBP k-anonymity, FR-ACC-08) hanya di produksi — memanggil API eksternal.
        return app()->isProduction() ? $rule->uncompromised() : $rule;
    }

    private function assertCaptcha(Request $request, ?string $token): void
    {
        if (! $this->captcha->verify($token, $request->ip())) {
            throw new DomainRuleException('Verifikasi CAPTCHA gagal. Muat ulang dan coba lagi.', 'CAPTCHA_FAILED', 422);
        }
    }
}
