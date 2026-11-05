<?php

namespace Modules\Admission\Enums;

/**
 * Status butir dalam bank soal. Butir baru selalu `draft`. Butir menjadi `approved` saat
 * versinya disetujui (lihat AssessmentApprovalService), dan tetap `approved` setelah publikasi.
 */
enum AssessmentItemStatus: string
{
    case Draft = 'draft';
    case Approved = 'approved';

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
