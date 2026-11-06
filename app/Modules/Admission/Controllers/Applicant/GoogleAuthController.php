<?php

namespace Modules\Admission\Controllers\Applicant;

use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Laravel\Sanctum\NewAccessToken;
use Modules\Admission\Services\Account\GoogleAuthService;
use Modules\Admission\Support\CoreFormFields;

/**
 * Daftar/masuk pendaftar dengan Google (lihat GoogleAuthService untuk alur
 * dan kebijakan keamanan). Hanya `callback` yang dibuka langsung oleh
 * browser (redirect dari Google); endpoint lain dipanggil SPA via JSON.
 */
class GoogleAuthController extends ApplicantController
{
    /** PKCE verifier/challenge milik SPA: base64url, RFC 7636 §4.1. */
    private const CHALLENGE_RULE = 'regex:/^[A-Za-z0-9_-]{43}$/';

    private const VERIFIER_RULE = 'regex:/^[A-Za-z0-9_-]{43,128}$/';

    public function __construct(private readonly GoogleAuthService $google) {}

    /** GET …/auth/google/redirect → { authorization_url } untuk dibuka SPA. */
    public function redirect(Request $request): JsonResponse
    {
        $data = $request->validate(['client_challenge' => ['required', 'string', self::CHALLENGE_RULE]]);

        return ApiResponse::success(['authorization_url' => $this->google->start($data['client_challenge'])]);
    }

    /** GET …/auth/google/callback — redirect URI terdaftar di Google Cloud Console. */
    public function callback(Request $request): RedirectResponse
    {
        $url = $this->google->callback(
            $this->query($request, 'state'),
            $this->query($request, 'code'),
            $this->query($request, 'error'),
        );

        // Tiket ada di fragmen URL; Referrer-Policy mencegah URL callback (berisi code) bocor ke situs lain.
        return redirect()->away($url)->withHeaders(['Cache-Control' => 'no-store', 'Referrer-Policy' => 'no-referrer']);
    }

    /** POST …/auth/google/session — tukar tiket: masuk, atau info langkah tautkan/daftar. */
    public function session(Request $request): JsonResponse
    {
        $data = $this->validateTicket($request);
        $result = $this->google->resume($data['ticket'], $data['client_verifier'], $this->device($request, $data), $request->ip());

        return ApiResponse::success($this->present($result));
    }

    /** POST …/auth/google/link — tautkan ke akun lama ber-email sama (wajib sandi akun lama). */
    public function link(Request $request): JsonResponse
    {
        $data = $this->validateTicket($request, ['password' => ['required', 'string', 'max:200']]);
        $result = $this->google->link($data['ticket'], $data['client_verifier'], $data['password'], $this->device($request, $data), $request->ip());

        return ApiResponse::success($this->present($result), 'Akun Google berhasil dihubungkan.');
    }

    /** POST …/auth/google/register — lengkapi data & persetujuan untuk akun baru. */
    public function register(Request $request): JsonResponse
    {
        $data = $this->validateTicket($request, [
            'name' => ['required', 'string', 'min:3', 'max:150'],
            'phone' => ['nullable', 'string', 'regex:/^\+?[0-9\s\-]{8,20}$/'],
            'consents' => ['required', 'array'],
            'consents.privacy_policy' => ['accepted'],
            'consents.terms' => ['accepted'],
            'consents.marketing_optional' => ['nullable', 'boolean'],
            'info_source' => ['nullable', 'in:'.implode(',', CoreFormFields::INFO_SOURCES)],
        ]);

        $result = $this->google->register($data['ticket'], $data['client_verifier'], [
            'name' => $data['name'],
            'phone' => $data['phone'] ?? null,
            'info_source' => $data['info_source'] ?? null,
            'marketing_optional' => (bool) ($data['consents']['marketing_optional'] ?? false),
        ], $this->device($request, $data), $request->ip(), $request->userAgent());

        return ApiResponse::success($this->present($result), 'Akun dibuat dengan Google.', status: 201);
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    private function validateTicket(Request $request, array $extra = []): array
    {
        return $request->validate([
            'ticket' => ['required', 'string', 'max:128'],
            'client_verifier' => ['required', 'string', self::VERIFIER_RULE],
            'device_name' => ['nullable', 'string', 'max:100'],
            ...$extra,
        ]);
    }

    /**
     * Bentuk respons `authenticated` sama dengan POST /auth/login.
     *
     * @param  array<string, mixed>  $result
     * @return array<string, mixed>
     */
    private function present(array $result): array
    {
        if ($result['status'] !== 'authenticated') {
            return $result;
        }

        /** @var NewAccessToken $token */
        $token = $result['token'];

        return [
            'status' => 'authenticated',
            'token' => $token->plainTextToken,
            'expires_at' => $token->accessToken->expires_at?->toIso8601String(),
            'account' => AuthController::presentAccount($result['account']),
        ];
    }

    /** @param  array<string, mixed>  $data */
    private function device(Request $request, array $data): string
    {
        return (string) ($data['device_name'] ?? $request->userAgent() ?? 'browser');
    }

    private function query(Request $request, string $key): ?string
    {
        $value = $request->query($key);

        return is_string($value) ? mb_substr($value, 0, 2048) : null;
    }
}
