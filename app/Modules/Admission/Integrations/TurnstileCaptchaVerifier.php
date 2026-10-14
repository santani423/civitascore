<?php

namespace Modules\Admission\Integrations;

use Illuminate\Support\Facades\Http;
use Modules\Admission\Contracts\CaptchaVerifier;
use Throwable;

/** Cloudflare Turnstile — gagal tertutup bila layanan tidak dapat dihubungi. */
class TurnstileCaptchaVerifier implements CaptchaVerifier
{
    public function verify(?string $token, ?string $ip): bool
    {
        if (! $token) {
            return false;
        }

        try {
            $response = Http::asForm()->timeout(5)->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                'secret' => (string) config('pmb.captcha.turnstile_secret'),
                'response' => $token,
                'remoteip' => $ip,
            ]);

            return (bool) $response->json('success', false);
        } catch (Throwable) {
            return false;
        }
    }
}
