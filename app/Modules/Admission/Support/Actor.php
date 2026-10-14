<?php

namespace Modules\Admission\Support;

use App\Models\User;
use Modules\Admission\Models\ApplicantAccount;

/**
 * Pelaku sebuah aksi domain: staf (`users`), pendaftar (akun PMB), atau
 * sistem (job/scheduler). Dipakai untuk riwayat status (`actor_type`).
 */
final class Actor
{
    private function __construct(
        public readonly string $type,
        public readonly ?string $id,
        public readonly ?User $user = null,
        public readonly ?ApplicantAccount $applicant = null,
    ) {}

    public static function user(User $user): self
    {
        return new self('user', (string) $user->getKey(), user: $user);
    }

    public static function applicant(ApplicantAccount $account): self
    {
        return new self('applicant', (string) $account->getKey(), applicant: $account);
    }

    public static function system(): self
    {
        return new self('system', null);
    }

    public static function from(mixed $actor): self
    {
        return match (true) {
            $actor instanceof self => $actor,
            $actor instanceof User => self::user($actor),
            $actor instanceof ApplicantAccount => self::applicant($actor),
            default => self::system(),
        };
    }

    public function isUser(): bool
    {
        return $this->user !== null;
    }

    public function userId(): ?string
    {
        return $this->user?->getKey();
    }
}
