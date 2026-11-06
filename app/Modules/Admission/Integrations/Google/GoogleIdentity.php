<?php

namespace Modules\Admission\Integrations\Google;

/** Klaim ID token Google yang sudah tervalidasi. */
final readonly class GoogleIdentity
{
    public function __construct(
        public string $subject,
        public string $email,
        public bool $emailVerified,
        public ?string $name = null,
        public ?string $picture = null,
        public ?string $hostedDomain = null,
    ) {}

    /** @param  array<string, mixed>  $claims */
    public static function fromClaims(array $claims): self
    {
        $verified = $claims['email_verified'] ?? false;
        $string = fn (string $key): ?string => isset($claims[$key]) && is_string($claims[$key]) && $claims[$key] !== '' ? $claims[$key] : null;

        return new self(
            subject: (string) $claims['sub'],
            email: mb_strtolower(trim((string) $claims['email'])),
            // Google mengirim boolean; versi lama kadang string "true".
            emailVerified: $verified === true || $verified === 'true',
            name: $string('name'),
            picture: $string('picture'),
            hostedDomain: $string('hd'),
        );
    }
}
