<?php

namespace Modules\ApprovalWorkflow\Contracts;

/**
 * Kontrak pada model `requestable` yang mewajibkan pemisahan tugas: pemohon
 * (approval_requests.requested_by) — dan pengguna lain yang dikecualikan
 * requestable — tidak boleh memutuskan langkah mana pun atas pengajuan itu
 * (docs/pmb/08 §4, BR-20, BR-30). Opt-in agar perilaku alur persetujuan
 * modul lain yang sudah berjalan tidak berubah.
 */
interface RequiresDistinctApprover
{
    /**
     * ID pengguna tambahan yang tidak boleh memutuskan pengajuan ini
     * (mis. verifikator bukti bayar untuk refund transaksi yang sama).
     *
     * @return array<int, string>
     */
    public function excludedApproverIds(): array;
}
