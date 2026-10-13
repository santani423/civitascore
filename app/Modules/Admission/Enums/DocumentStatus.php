<?php

namespace Modules\Admission\Enums;

use Modules\Admission\Support\TransitionsState;

/** SM-03 — dokumen persyaratan. */
enum DocumentStatus: string
{
    use TransitionsState;

    case Missing = 'missing';
    case Uploaded = 'uploaded';
    case InReview = 'in_review';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case RevisionRequested = 'revision_requested';

    /** @return array<int, self> */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Missing => [self::Uploaded],
            // Dokumen daftar ulang diperiksa tanpa klaim antrean, sehingga
            // keputusan boleh langsung dari `uploaded`.
            self::Uploaded => [self::Uploaded, self::InReview, self::Approved, self::Rejected, self::RevisionRequested],
            self::InReview => [self::Approved, self::Rejected, self::RevisionRequested, self::Uploaded],
            self::Approved => [self::RevisionRequested],
            self::Rejected => [self::Uploaded],
            self::RevisionRequested => [self::Uploaded],
        };
    }
}
