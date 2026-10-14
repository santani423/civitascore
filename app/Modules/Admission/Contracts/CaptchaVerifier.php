<?php

namespace Modules\Admission\Contracts;

/** Verifikasi CAPTCHA untuk registrasi, cek status, lupa sandi (11 §B.8). */
interface CaptchaVerifier
{
    public function verify(?string $token, ?string $ip): bool;
}
