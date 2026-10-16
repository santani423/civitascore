<?php

namespace Modules\Academic\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Modules\Academic\Models\KrsSubmission;

/**
 * KRS mahasiswa disetujui (baris menjadi Enrolled). Dipancarkan setelah
 * commit — konsumen: Modul KEU (tagihan biaya per SKS, BR-KEU-10).
 * Nama sesuai kanon docs/academic-learning/06-integrasi-arsitektur.md.
 */
class KrsSubmissionApproved
{
    use Dispatchable;

    public function __construct(public readonly KrsSubmission $submission) {}
}
