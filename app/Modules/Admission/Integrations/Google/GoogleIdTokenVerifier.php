<?php

namespace Modules\Admission\Integrations\Google;

use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Validasi ID token Google sesuai OpenID Connect Core §3.1.3.7 dan panduan
 * Google ("Verify the integrity of the ID token"):
 *  - tanda tangan RS256 terhadap kunci publik Google (JWKS, di-cache sesuai
 *    Cache-Control; diambil ulang sekali bila `kid` belum dikenal → rotasi kunci),
 *  - `iss` = accounts.google.com, `aud` = client ID aplikasi ini (dan `azp` bila
 *    `aud` berisi banyak nilai), `exp`/`iat` wajib ada dan masih berlaku,
 *  - `nonce` sama dengan yang dikirim saat redirect (anti-replay).
 * Algoritme diambil dari JWKS, bukan dari header token (mencegah alg confusion).
 */
class GoogleIdTokenVerifier
{
    public const ISSUERS = ['https://accounts.google.com', 'accounts.google.com'];

    public const JWKS_URL = 'https://www.googleapis.com/oauth2/v3/certs';

    private const CACHE_KEY = 'pmb:google:jwks';

    private const LEEWAY_SECONDS = 60;

    public function verify(string $idToken, string $expectedNonce): GoogleIdentity
    {
        $clientId = (string) config('services.google.client_id');

        if ($clientId === '') {
            throw new GoogleAuthException('GOOGLE_CLIENT_ID belum diatur.');
        }

        $claims = $this->decode($idToken);

        if (! in_array($claims['iss'] ?? null, self::ISSUERS, true)) {
            throw new GoogleAuthException('Issuer ID token tidak valid.');
        }

        $audience = $claims['aud'] ?? null;
        $audienceOk = is_array($audience)
            ? in_array($clientId, $audience, true) && ($claims['azp'] ?? null) === $clientId
            : $audience === $clientId;

        if (! $audienceOk) {
            throw new GoogleAuthException('Audience ID token tidak sesuai client ID.');
        }

        if (! isset($claims['exp'], $claims['iat']) || ! is_numeric($claims['exp']) || ! is_numeric($claims['iat'])) {
            throw new GoogleAuthException('ID token tanpa exp/iat.');
        }

        if (! is_string($claims['nonce'] ?? null) || ! hash_equals($expectedNonce, $claims['nonce'])) {
            throw new GoogleAuthException('Nonce ID token tidak cocok.');
        }

        if (! is_string($claims['sub'] ?? null) || $claims['sub'] === '' || strlen($claims['sub']) > 64) {
            throw new GoogleAuthException('Klaim sub tidak valid.');
        }

        if (! is_string($claims['email'] ?? null) || filter_var($claims['email'], FILTER_VALIDATE_EMAIL) === false) {
            throw new GoogleAuthException('ID token tidak memuat email yang valid.');
        }

        return GoogleIdentity::fromClaims($claims);
    }

    /** @return array<string, mixed> */
    private function decode(string $idToken): array
    {
        $previousLeeway = JWT::$leeway;
        JWT::$leeway = self::LEEWAY_SECONDS;

        try {
            try {
                $payload = JWT::decode($idToken, JWK::parseKeySet($this->keys(), 'RS256'));
            } catch (\UnexpectedValueException $e) {
                // `kid` tidak ada di JWKS cache → Google merotasi kunci; ambil ulang sekali.
                if (! str_contains($e->getMessage(), 'kid')) {
                    throw $e;
                }

                $payload = JWT::decode($idToken, JWK::parseKeySet($this->keys(refresh: true), 'RS256'));
            }
        } catch (GoogleAuthException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new GoogleAuthException('ID token gagal diverifikasi: '.$e->getMessage(), previous: $e);
        } finally {
            JWT::$leeway = $previousLeeway;
        }

        return json_decode((string) json_encode($payload), true) ?: [];
    }

    /** @return array<string, mixed> */
    private function keys(bool $refresh = false): array
    {
        if (! $refresh && is_array($cached = Cache::get(self::CACHE_KEY))) {
            return $cached;
        }

        try {
            $response = Http::acceptJson()->timeout(5)->get(self::JWKS_URL)->throw();
        } catch (Throwable $e) {
            throw new GoogleAuthException('Kunci publik Google tidak dapat diambil: '.$e->getMessage(), previous: $e);
        }

        $jwks = $response->json();

        if (! is_array($jwks) || empty($jwks['keys'])) {
            throw new GoogleAuthException('Format JWKS Google tidak dikenal.');
        }

        // Ikuti Cache-Control max-age dari Google (biasanya beberapa jam), batasi 1 menit–24 jam.
        preg_match('/max-age=(\d+)/', (string) $response->header('Cache-Control'), $match);
        $ttl = max(60, min((int) ($match[1] ?? 3600), 86400));
        Cache::put(self::CACHE_KEY, $jwks, $ttl);

        return $jwks;
    }
}
