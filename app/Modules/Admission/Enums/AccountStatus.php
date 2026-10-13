<?php

namespace Modules\Admission\Enums;

use Modules\Admission\Support\TransitionsState;

/** SM-01 — akun pendaftar (docs/pmb/09). */
enum AccountStatus: string
{
    use TransitionsState;

    case Unverified = 'unverified';
    case Active = 'active';
    case Locked = 'locked';
    case Suspended = 'suspended';
    case Deactivated = 'deactivated';

    /** @return array<int, self> */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Unverified => [self::Active, self::Deactivated],
            self::Active => [self::Locked, self::Suspended, self::Deactivated],
            self::Locked => [self::Active],
            self::Suspended => [self::Active, self::Deactivated],
            self::Deactivated => [],
        };
    }
}
