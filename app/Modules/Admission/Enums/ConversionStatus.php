<?php

namespace Modules\Admission\Enums;

use Modules\Admission\Support\TransitionsState;

/** SM-11 — konversi menjadi mahasiswa. */
enum ConversionStatus: string
{
    use TransitionsState;

    case Pending = 'pending';
    case Processing = 'processing';
    case Succeeded = 'succeeded';
    case Failed = 'failed';
    case ManualIntervention = 'manual_intervention';

    /** @return array<int, self> */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Pending => [self::Processing],
            self::Processing => [self::Succeeded, self::Pending, self::Failed, self::ManualIntervention],
            self::Failed => [self::Pending],
            self::ManualIntervention => [self::Processing, self::Pending],
            self::Succeeded => [],
        };
    }
}
