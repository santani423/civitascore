<?php

use Firebase\JWT\JWT;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Modules\Admission\Integrations\Google\GoogleAuthException;
use Modules\Admission\Integrations\Google\GoogleIdTokenVerifier;
use Modules\Admission\Integrations\Google\SocialiteGoogleIdentityProvider;
use phpseclib4\Crypt\RSA;

/*
| Validasi ID token Google dengan kunci RSA uji: JWKS Google dipalsukan lewat
| Http::fake, token ditandatangani RS256 seperti token asli Google.
*/

const PMB_GOOGLE_TEST_CLIENT = '1234567890-abc.apps.googleusercontent.com';

beforeEach(function (): void {
    config(['services.google.client_id' => PMB_GOOGLE_TEST_CLIENT]);
    Cache::flush();

    $this->key = RSA::createKey(2048);
    $this->jwks = ['keys' => [json_decode($this->key->getPublicKey()->toString('JWK'), true)['keys'][0] + ['kid' => 'kunci-1', 'alg' => 'RS256', 'use' => 'sig']]];
    $this->jwksCalls = 0;
    Http::fake([GoogleIdTokenVerifier::JWKS_URL => function () {
        $this->jwksCalls++;

        return Http::response($this->jwks, 200, ['Cache-Control' => 'public, max-age=19800']);
    }]);

    $this->sign = fn (array $overrides = [], ?RSA\PrivateKey $key = null, string $kid = 'kunci-1'): string => JWT::encode([
        'iss' => 'https://accounts.google.com',
        'aud' => PMB_GOOGLE_TEST_CLIENT,
        'azp' => PMB_GOOGLE_TEST_CLIENT,
        'sub' => '110248495921238986420',
        'email' => 'Maria@Gmail.com',
        'email_verified' => true,
        'name' => 'Maria Angelina',
        'picture' => 'https://lh3.googleusercontent.com/a/foto',
        'nonce' => 'nonce-uji',
        'iat' => time(),
        'exp' => time() + 3600,
        ...$overrides,
    ], ($key ?? $this->key)->toString('PKCS8'), 'RS256', $kid);
});

it('accepts a correctly signed Google ID token and maps its claims', function (): void {
    $identity = (new GoogleIdTokenVerifier)->verify(($this->sign)(), 'nonce-uji');

    expect($identity->subject)->toBe('110248495921238986420')
        ->and($identity->email)->toBe('maria@gmail.com')
        ->and($identity->emailVerified)->toBeTrue()
        ->and($identity->name)->toBe('Maria Angelina');

    // JWKS di-cache: verifikasi kedua tidak mengambil ulang kunci.
    (new GoogleIdTokenVerifier)->verify(($this->sign)(['iss' => 'accounts.google.com']), 'nonce-uji');
    expect($this->jwksCalls)->toBe(1);
});

it('reports email_verified=false without rejecting the token', function (): void {
    expect((new GoogleIdTokenVerifier)->verify(($this->sign)(['email_verified' => false]), 'nonce-uji')->emailVerified)->toBeFalse();
});

it('rejects tokens with a bad issuer, audience, nonce, expiry, or signature', function (array $overrides, ?string $nonce, bool $foreignKey): void {
    $key = $foreignKey ? RSA::createKey(2048) : null;

    expect(fn () => (new GoogleIdTokenVerifier)->verify(($this->sign)($overrides, $key), $nonce ?? 'nonce-uji'))
        ->toThrow(GoogleAuthException::class);
})->with([
    'issuer palsu' => [['iss' => 'https://evil.example.com'], null, false],
    'audience aplikasi lain' => [['aud' => 'aplikasi-lain.apps.googleusercontent.com'], null, false],
    'aud jamak tanpa azp' => [['aud' => [PMB_GOOGLE_TEST_CLIENT, 'lain'], 'azp' => 'lain'], null, false],
    'nonce berbeda (replay)' => [[], 'nonce-lain', false],
    'kedaluwarsa' => [['iat' => time() - 7200, 'exp' => time() - 3600], null, false],
    'iat di masa depan' => [['iat' => time() + 3600, 'exp' => time() + 7200], null, false],
    'tanpa exp' => [['exp' => null], null, false],
    'email tidak valid' => [['email' => 'bukan-email'], null, false],
    'tanda tangan kunci lain' => [[], null, true],
]);

it('builds the authorization URL and exchanges the code with PKCE through Socialite', function (): void {
    $redirect = 'https://api.example.test/api/v1/pmb/applicant/auth/google/callback';
    $history = [];
    $stack = HandlerStack::create(new MockHandler([
        new GuzzleResponse(200, ['Content-Type' => 'application/json'], (string) json_encode(['access_token' => 'ya29.uji', 'expires_in' => 3599, 'token_type' => 'Bearer', 'id_token' => ($this->sign)()])),
        new GuzzleResponse(400, ['Content-Type' => 'application/json'], '{"error":"invalid_grant"}'),
    ]));
    $stack->push(Middleware::history($history));
    config(['services.google' => ['client_id' => PMB_GOOGLE_TEST_CLIENT, 'client_secret' => 'GOCSPX-rahasia-uji', 'redirect' => $redirect, 'guzzle' => ['handler' => $stack]]]);

    $provider = app(SocialiteGoogleIdentityProvider::class);
    expect($provider->isConfigured())->toBeTrue();

    $url = $provider->authorizationUrl('state-uji', 'nonce-uji', 'challenge-uji');
    parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
    expect($url)->toStartWith('https://accounts.google.com/o/oauth2/auth?')
        ->and($query)->toMatchArray([
            'client_id' => PMB_GOOGLE_TEST_CLIENT, 'redirect_uri' => $redirect, 'response_type' => 'code', 'state' => 'state-uji',
            'nonce' => 'nonce-uji', 'code_challenge' => 'challenge-uji', 'code_challenge_method' => 'S256', 'prompt' => 'select_account',
        ])
        ->and(explode(' ', $query['scope']))->toContain('openid', 'email', 'profile')
        ->and($url)->not->toContain('GOCSPX-rahasia-uji');

    $identity = $provider->identityFromCode('kode-uji', 'verifier-uji', 'nonce-uji');
    parse_str((string) $history[0]['request']->getBody(), $form);
    expect($identity->email)->toBe('maria@gmail.com')
        ->and($form)->toMatchArray(['grant_type' => 'authorization_code', 'code' => 'kode-uji', 'code_verifier' => 'verifier-uji', 'client_secret' => 'GOCSPX-rahasia-uji', 'redirect_uri' => $redirect]);

    // Code ditolak Google (kedaluwarsa / dipakai ulang) → GoogleAuthException, bukan error 500.
    expect(fn () => $provider->identityFromCode('kode-bekas', 'verifier-uji', 'nonce-uji'))->toThrow(GoogleAuthException::class);
});

it('refetches the JWKS once when Google rotates its signing key', function (): void {
    (new GoogleIdTokenVerifier)->verify(($this->sign)(), 'nonce-uji');

    $rotated = RSA::createKey(2048);
    $this->jwks = ['keys' => [json_decode($rotated->getPublicKey()->toString('JWK'), true)['keys'][0] + ['kid' => 'kunci-2', 'alg' => 'RS256', 'use' => 'sig']]];

    $identity = (new GoogleIdTokenVerifier)->verify(($this->sign)([], $rotated, 'kunci-2'), 'nonce-uji');

    expect($identity->subject)->toBe('110248495921238986420')->and($this->jwksCalls)->toBe(2);
});
