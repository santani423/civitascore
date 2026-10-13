<?php

namespace Modules\Admission\Enums;

use Modules\Admission\Support\TransitionsState;

/** SM-06 — verifikasi administrasi (tabel transisi 05 §G.3). */
enum VerificationStatus: string
{
    use TransitionsState;

    case NotStarted = 'not_started';
    case InReview = 'in_review';
    case RevisionRequested = 'revision_requested';
    case Verified = 'verified';
    case Rejected = 'rejected';

    /** @return array<int, self> */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::NotStarted => [self::InReview],
            self::InReview => [self::Verified, self::RevisionRequested, self::Rejected, self::NotStarted],
            self::RevisionRequested => [self::InReview, self::Rejected],
            self::Verified => [self::InReview],
            self::Rejected => [self::InReview],
        };
    }
}
