<?php

namespace Modules\FileManagement\Policies;

use App\Models\User;
use Modules\FileManagement\Contracts\RestrictsFileAccess;
use Modules\FileManagement\Models\FileUpload;

class FileUploadPolicy
{
    public function view(User $user, FileUpload $fileUpload): bool
    {
        if ($this->isRestricted($fileUpload)) {
            $owner = $fileUpload->fileable;

            return $user->id === $fileUpload->uploaded_by
                || ($owner instanceof RestrictsFileAccess && $owner->allowsFileAccess($user));
        }

        return $fileUpload->is_public
            || $user->id === $fileUpload->uploaded_by
            || $user->hasPermissionTo('file_uploads.read');
    }

    public function download(User $user, FileUpload $fileUpload): bool
    {
        return $this->view($user, $fileUpload);
    }

    public function delete(User $user, FileUpload $fileUpload): bool
    {
        // Berkas yang sudah terlampir ke data sensitif hanya boleh dilepas
        // lewat modul pemiliknya — menghapusnya langsung di sini akan
        // meninggalkan record yang menunjuk berkas yang sudah hilang.
        if ($this->isRestricted($fileUpload)) {
            return false;
        }

        return $user->id === $fileUpload->uploaded_by || $user->hasPermissionTo('file_uploads.delete');
    }

    /**
     * Dicek dari tipe kelasnya, bukan dari instance — pemilik yang sudah
     * di-soft-delete (fileable = null) tetap diperlakukan terbatas, tidak
     * jatuh kembali ke izin generik file_uploads.read.
     */
    private function isRestricted(FileUpload $fileUpload): bool
    {
        return $fileUpload->fileable_type !== null
            && is_a($fileUpload->fileable_type, RestrictsFileAccess::class, true);
    }
}
