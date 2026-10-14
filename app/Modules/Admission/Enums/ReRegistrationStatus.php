<?php

namespace Modules\Admission\Enums;

use Modules\Admission\Support\TransitionsState;

/**
 * SM-10 — daftar ulang. Pemulihan terbatas: declined → awaiting_confirmation
 * (≤ 24 jam, Admin PMB) dan expired → in_progress (LATE_CALLBACK).
 */
enum ReRegistrationStatus: string
{
    use TransitionsState;

    case AwaitingConfirmation = 'awaiting_confirmation';
    case Confirmed = 'confirmed';
    case InProgress = 'in_progress';
    case Submitted = 'submitted';
    case RevisionRequested = 'revision_requested';
    case Approved = 'approved';
    case Enrolled = 'enrolled';
    case Declined = 'declined';
    case Expired = 'expired';
    case Cancelled = 'cancelled';

    /** @return array<int, self> */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::AwaitingConfirmation => [self::Confirmed, self::Declined, self::Expired, self::Cancelled],
            self::Confirmed => [self::InProgress, self::Submitted, self::Expired, self::Cancelled],
            self::InProgress => [self::Submitted, self::Expired, self::Cancelled],
            self::Submitted => [self::Approved, self::RevisionRequested, self::Cancelled],
            self::RevisionRequested => [self::Submitted, self::InProgress, self::Expired, self::Cancelled],
            self::Approved => [self::Enrolled, self::Cancelled],
            self::Declined => [self::AwaitingConfirmation],
            self::Expired => [self::InProgress, self::AwaitingConfirmation],
            self::Enrolled, self::Cancelled => [],
        };
    }

    public function isActive(): bool
    {
        return in_array($this, [self::AwaitingConfirmation, self::Confirmed, self::InProgress, self::Submitted, self::RevisionRequested, self::Approved], true);
    }

    public function canExpire(): bool
    {
        return in_array($this, [self::AwaitingConfirmation, self::Confirmed, self::InProgress, self::RevisionRequested], true);
    }
}
