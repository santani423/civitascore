<?php

namespace Modules\Admission\Tests\Support;

use Modules\Admission\Contracts\GoogleIdentityProvider;
use Modules\Admission\Integrations\Google\GoogleAuthException;
use Modules\Admission\Integrations\Google\GoogleIdentity;

/**
 * Pengganti Google untuk tes fitur: tidak ada panggilan jaringan. Validasi
 * ID token yang sebenarnya diuji terpisah di GoogleIdTokenVerifierTest.
 */
final class FakeGoogleIdentityProvider implements GoogleIdentityProvider
{
    public bool $configured = true;

    public ?GoogleIdentity $identity = null;

    public bool $fail = false;

    /** @var array<string, string> */
    public array $authorization = [];

    /** @var array<string, string> */
    public array $exchange = [];

    public function isConfigured(): bool
    {
        return $this->configured;
    }

    public function authorizationUrl(string $state, string $nonce, string $codeChallenge): string
    {
        $this->authorization = ['state' => $state, 'nonce' => $nonce, 'code_challenge' => $codeChallenge];

        return 'https://accounts.google.com/o/oauth2/auth?'.http_build_query($this->authorization + ['code_challenge_method' => 'S256']);
    }

    public function identityFromCode(string $code, string $codeVerifier, string $nonce): GoogleIdentity
    {
        $this->exchange = ['code' => $code, 'code_verifier' => $codeVerifier, 'nonce' => $nonce];

        if ($this->fail || $this->identity === null) {
            throw new GoogleAuthException('invalid_grant');
        }

        return $this->identity;
    }
}
