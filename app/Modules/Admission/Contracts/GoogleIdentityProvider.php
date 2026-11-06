<?php

namespace Modules\Admission\Contracts;

use Modules\Admission\Integrations\Google\GoogleAuthException;
use Modules\Admission\Integrations\Google\GoogleIdentity;

/**
 * Penyedia identitas Google (OAuth 2.0 authorization code + PKCE, OpenID
 * Connect). Implementasi produksi: SocialiteGoogleIdentityProvider; tes
 * mengganti binding ini agar tidak memanggil Google.
 */
interface GoogleIdentityProvider
{
    /** Client ID & secret tersedia — bila tidak, fitur Google dimatikan. */
    public function isConfigured(): bool;

    /** URL layar persetujuan Google (state, nonce, dan PKCE S256 sudah disertakan). */
    public function authorizationUrl(string $state, string $nonce, string $codeChallenge): string;

    /**
     * Menukar authorization code (dengan code_verifier PKCE) lalu memvalidasi
     * ID token: tanda tangan, issuer, audience, kedaluwarsa, dan nonce.
     *
     * @throws GoogleAuthException
     */
    public function identityFromCode(string $code, string $codeVerifier, string $nonce): GoogleIdentity;
}
