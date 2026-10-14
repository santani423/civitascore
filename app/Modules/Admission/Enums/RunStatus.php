<?php

namespace Modules\Admission\Enums;

use Modules\Admission\Support\TransitionsState;

/**
 * SM-08 — selection run. `failed` ditambahkan untuk kegagalan job hitung
 * (tidak ada di 09; run gagal dapat digantikan run baru).
 */
enum RunStatus: string
{
    use TransitionsState;

    case Draft = 'draft';
    case Computed = 'computed';
    case UnderReview = 'under_review';
    case PendingApproval = 'pending_approval';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Published = 'published';
    case Superseded = 'superseded';
    case Failed = 'failed';

    /** @return array<int, self> */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Draft => [self::Computed, self::Failed, self::Superseded],
            self::Computed => [self::UnderReview, self::Superseded],
            self::UnderReview => [self::PendingApproval, self::Superseded],
            self::PendingApproval => [self::Approved, self::UnderReview, self::Rejected],
            self::Approved => [self::Published],
            self::Rejected => [self::Superseded],
            self::Published => [self::Superseded],
            self::Failed => [self::Superseded],
            self::Superseded => [],
        };
    }

    public function blocksNewRun(): bool
    {
        return in_array($this, [self::PendingApproval, self::Approved], true);
    }
}
