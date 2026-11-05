<?php

namespace Modules\Notification\Support;

use App\Support\Tenancy\TenantContext;
use Modules\Tenancy\Models\University;

/**
 * Identitas pengirim di kepala & kaki email (resources/views/vendor/mail):
 * kampus tenant aktif — sama dengan SiteMark portal PMB — atau nama aplikasi
 * bila email dikirim tanpa konteks tenant.
 */
final class MailBrand
{
    /**
     * @param  array<string, string>  $contact  kunci: address, email, phone, whatsapp, hours, website
     */
    public function __construct(
        public readonly string $name,
        public readonly string $fullName,
        public readonly ?string $subtitle,
        public readonly string $initials,
        public readonly ?string $logoUrl,
        public readonly array $contact,
    ) {}

    public static function current(): self
    {
        $universityId = app(TenantContext::class)->universityId();
        $university = $universityId !== null ? University::query()->find($universityId) : null;

        $appName = (string) config('app.name');
        $fullName = (string) ($university?->name ?: $appName);
        $name = (string) ($university?->short_name ?: $fullName);

        return new self(
            name: $name,
            fullName: $fullName,
            subtitle: $fullName !== $name ? $fullName : null,
            initials: self::initials($name),
            logoUrl: config('mail.brand.logo_url') ?: null,
            contact: array_filter([
                'email' => (string) $university?->email,
                'phone' => (string) $university?->phone,
                'website' => (string) $university?->website,
            ]),
        );
    }

    /** "Atma Jaya" → "AJ", "UND" → "UN". */
    private static function initials(string $name): string
    {
        $words = preg_split('/\s+/u', trim($name), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        $initials = count($words) > 1
            ? mb_substr($words[0], 0, 1).mb_substr($words[1], 0, 1)
            : mb_substr($name, 0, 2);

        return mb_strtoupper($initials);
    }
}
