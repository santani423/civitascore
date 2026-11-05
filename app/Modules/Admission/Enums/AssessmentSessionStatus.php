<?php

namespace Modules\Admission\Enums;

use Modules\Admission\Support\TransitionsState;

/**
 * Status sesi asesmen peserta. Skor dihitung di transaksi yang sama dengan pengiriman,
 * sehingga `submitted` dan `expired` selalu sudah memiliki skor (atau tidak memiliki
 * skor bila jawaban belum lengkap saat waktu habis). `invalidated` adalah keputusan admin
 * dengan alasan dan audit; sesi yang sudah dipublikasikan tidak dapat diinvalidasi.
 */
enum AssessmentSessionStatus: string
{
    use TransitionsState;

    case InProgress = 'in_progress';
    case Submitted = 'submitted';
    case Expired = 'expired';
    case Published = 'published';
    case Invalidated = 'invalidated';

    /** @return array<int, self> */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::InProgress => [self::Submitted, self::Expired, self::Invalidated],
            self::Submitted => [self::Published, self::Invalidated],
            self::Expired => [self::Published, self::Invalidated],
            self::Published => [],
            self::Invalidated => [],
        };
    }
}
