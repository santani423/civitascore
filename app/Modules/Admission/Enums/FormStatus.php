<?php

namespace Modules\Admission\Enums;

use Modules\Admission\Support\TransitionsState;

/**
 * SM-02 — formulir. `locked → submitted` ditambahkan untuk override
 * verifikasi oleh Admin PMB (05 §G.3 verified/rejected → in_review): data
 * tetap terkunci bagi pendaftar tetapi kembali ke antrean pemeriksaan.
 */
enum FormStatus: string
{
    use TransitionsState;

    case Draft = 'draft';
    case Submitted = 'submitted';
    case RevisionRequested = 'revision_requested';
    case Locked = 'locked';
    case Withdrawn = 'withdrawn';
    case Void = 'void';

    /** @return array<int, self> */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Draft => [self::Submitted, self::Void, self::Withdrawn],
            self::Submitted => [self::RevisionRequested, self::Locked, self::Withdrawn],
            self::RevisionRequested => [self::Submitted, self::Locked, self::Withdrawn],
            self::Locked => [self::RevisionRequested, self::Submitted],
            self::Withdrawn, self::Void => [],
        };
    }

    public function isActive(): bool
    {
        return ! in_array($this, [self::Withdrawn, self::Void], true);
    }
}
