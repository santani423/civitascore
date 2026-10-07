<?php

namespace Modules\FileManagement\Contracts;

use App\Models\User;

/**
 * Diimplementasikan oleh model pemilik berkas (`fileable`) yang isinya
 * sensitif — mis. dokumen kepegawaian. Berkas yang terlampir ke model
 * seperti ini TIDAK lagi bisa diakses lewat izin generik
 * `file_uploads.read`/`file_uploads.delete`; keputusan akses diserahkan
 * ke pemiliknya.
 */
interface RestrictsFileAccess
{
    public function allowsFileAccess(User $user): bool;
}
