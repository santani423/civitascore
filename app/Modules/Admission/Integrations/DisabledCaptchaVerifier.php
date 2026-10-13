<?php

namespace Modules\Admission\Integrations;

use Modules\Admission\Contracts\CaptchaVerifier;

/**
 * Driver `none` — hanya untuk lingkungan non-produksi (lokal/test).
 * AdmissionServiceProvider menolak driver ini di production.
 */
class DisabledCaptchaVerifier implements CaptchaVerifier
{
    public function verify(?string $token, ?string $ip): bool
    {
        return true;
    }
}
