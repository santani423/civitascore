<?php

namespace Modules\Admission\Enums;

/** Jenis entri pada riwayat review sesi. Catatan tidak pernah dihapus atau diubah. */
enum AssessmentReviewNoteKind: string
{
    case Note = 'note';
    case Flag = 'flag';
    case Unflag = 'unflag';
    case ReviewCompleted = 'review_completed';

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
