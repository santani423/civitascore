<?php

namespace Modules\Admission\Support;

/**
 * `identity_hash` = HMAC-SHA256(kunci tenant, identitas ternormalisasi)
 * (04 §C.3, 13 §B.5). Kunci tenant diturunkan dari APP_KEY + university_id
 * sehingga hash tidak dapat dibandingkan lintas tenant dan NIK berentropi
 * rendah tidak dapat di-brute-force tanpa kunci aplikasi.
 */
final class IdentityHasher
{
    public static function forNationalId(string $universityId, string $nik): string
    {
        return self::hash($universityId, 'nik:'.preg_replace('/\D/', '', $nik));
    }

    public static function forPassport(string $universityId, string $country, string $passport): string
    {
        return self::hash($universityId, 'passport:'.strtoupper($country).':'.strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $passport)));
    }

    public static function forDestination(string $universityId, string $destination): string
    {
        return self::hash($universityId, 'dest:'.mb_strtolower(trim($destination)));
    }

    private static function hash(string $universityId, string $value): string
    {
        $appKey = (string) config('app.key');
        $tenantKey = hash_hmac('sha256', 'pmb-identity-v1|'.$universityId, $appKey, true);

        return hash_hmac('sha256', $value, $tenantKey);
    }
}
