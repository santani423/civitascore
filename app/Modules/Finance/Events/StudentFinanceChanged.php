<?php

namespace Modules\Finance\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Kewajiban keuangan seorang mahasiswa berubah (tagihan terbit/dibayar/
 * dikoreksi, cicilan, award). Konsumen: evaluasi ulang cekal keuangan
 * (BR-KEU-66). Dipancarkan setelah commit agar tidak membaca data yang
 * kemudian di-rollback. Nama kanon `InvoiceStatusChanged` di dokumen
 * akademik dipenuhi oleh event ini (granularitas per mahasiswa).
 */
class StudentFinanceChanged implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(public readonly string $studentId, public readonly string $universityId) {}
}
