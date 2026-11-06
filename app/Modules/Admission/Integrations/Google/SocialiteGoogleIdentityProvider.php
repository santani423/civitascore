<?php

namespace Modules\Admission\Integrations\Google;

use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\GoogleProvider;
use Modules\Admission\Contracts\GoogleIdentityProvider;
use Throwable;

/**
 * Google lewat Laravel Socialite dalam mode stateless: API ini tanpa sesi
 * cookie, jadi `state`, `nonce`, dan PKCE dikelola GoogleAuthService
 * (cache sisi server) dan diteruskan lewat `with()`. Socialite dipakai untuk
 * membangun URL otorisasi dan menukar code (client secret hanya di sini).
 * Access token Google tidak disimpan — identitas diambil dari ID token yang
 * divalidasi GoogleIdTokenVerifier.
 */
class SocialiteGoogleIdentityProvider implements GoogleIdentityProvider
{
    public function __construct(private readonly GoogleIdTokenVerifier $verifier) {}

    public function isConfigured(): bool
    {
        return filled(config('services.google.client_id')) && filled(config('services.google.client_secret'));
    }

    public function authorizationUrl(string $state, string $nonce, string $codeChallenge): string
    {
        return $this->provider()->with([
            'state' => $state,
            'nonce' => $nonce,
            'code_challenge' => $codeChallenge,
            'code_challenge_method' => 'S256',
            // Selalu tampilkan pemilih akun: di komputer bersama (lab sekolah) mencegah
            // pendaftar tanpa sadar memakai akun Google orang lain yang masih login.
            'prompt' => 'select_account',
        ])->redirect()->getTargetUrl();
    }

    public function identityFromCode(string $code, string $codeVerifier, string $nonce): GoogleIdentity
    {
        try {
            $response = $this->provider()->with(['code_verifier' => $codeVerifier])->getAccessTokenResponse($code);
        } catch (Throwable $e) {
            throw new GoogleAuthException('Penukaran authorization code gagal: '.$e->getMessage(), previous: $e);
        }

        $idToken = is_array($response) ? ($response['id_token'] ?? null) : null;

        if (! is_string($idToken) || $idToken === '') {
            throw new GoogleAuthException('Respons token Google tidak memuat id_token.');
        }

        return $this->verifier->verify($idToken, $nonce);
    }

    /** Instans baru per panggilan: parameter `with()` tidak terbawa antar-request. */
    private function provider(): GoogleProvider
    {
        /** @var GoogleProvider $provider */
        $provider = Socialite::buildProvider(GoogleProvider::class, (array) config('services.google'));

        return $provider->stateless()->scopes(['openid', 'email', 'profile']);
    }
}
