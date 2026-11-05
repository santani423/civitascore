<?php

namespace Modules\Admission\Enums;

use Modules\Admission\Support\TransitionsState;

/**
 * Siklus versi instrumen (docs/one-atma-talent/05). Hanya `draft` yang dapat diubah.
 * `published` dan `archived` tidak pernah kembali ke status yang dapat diedit.
 */
enum AssessmentVersionStatus: string
{
    use TransitionsState;

    case Draft = 'draft';
    case InReview = 'in_review';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Published = 'published';
    case Archived = 'archived';

    /** @return array<int, self> */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Draft => [self::InReview],
            // Dikembalikan oleh approver kembali ke draft; ditolak perlu revisi lalu diajukan lagi.
            self::InReview => [self::Approved, self::Rejected, self::Draft],
            self::Rejected => [self::Draft],
            self::Approved => [self::Published, self::Draft],
            self::Published => [self::Archived],
            self::Archived => [],
        };
    }

    public function isEditable(): bool
    {
        return $this === self::Draft;
    }
}
