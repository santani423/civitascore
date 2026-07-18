<?php

namespace Modules\FileManagement\Policies;

use App\Models\User;
use Modules\FileManagement\Models\FileUpload;

class FileUploadPolicy
{
    public function view(User $user, FileUpload $fileUpload): bool
    {
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
        return $user->id === $fileUpload->uploaded_by || $user->hasPermissionTo('file_uploads.delete');
    }
}
