<?php

namespace Modules\HumanResource\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use Modules\FileManagement\Enums\FileUploadStatus;
use Modules\FileManagement\Models\FileUpload;

/**
 * Menautkan berkas yang sudah diunggah lewat POST /file-uploads ke record
 * SDM (mengisi fileable_type/id). Setelah tertaut, FileUploadPolicy
 * menyerahkan keputusan akses ke record tersebut (RestrictsFileAccess).
 *
 * Validasi: berkas harus milik tenant ini (query ter-scope TenantScoped →
 * id tenant lain = "tidak ada"), sudah lolos pemindaian, dan diunggah oleh
 * user yang sama — atau memang sudah tertaut ke record yang sama (edit
 * tanpa mengganti berkas). Mencegah melampirkan berkas milik orang lain
 * hanya dengan menebak id-nya.
 */
class HrFileAttacher
{
    public function resolve(?string $fileId, string $field, User $actor, ?Model $currentOwner = null): ?FileUpload
    {
        if ($fileId === null || $fileId === '') {
            return null;
        }

        $file = FileUpload::query()->find($fileId);

        $alreadyOwned = $file !== null
            && $currentOwner !== null
            && $file->fileable_type === $currentOwner->getMorphClass()
            && $file->fileable_id === $currentOwner->getKey();

        if ($file === null
            || (! $alreadyOwned && $file->fileable_type !== null)
            || (! $alreadyOwned && $file->uploaded_by !== $actor->id)
            || $file->status !== FileUploadStatus::Clean) {
            throw ValidationException::withMessages([$field => 'Berkas tidak valid atau tidak dapat dilampirkan.']);
        }

        return $file;
    }

    public function attach(?FileUpload $file, Model $owner): void
    {
        if ($file === null) {
            return;
        }

        $file->forceFill([
            'fileable_type' => $owner->getMorphClass(),
            'fileable_id' => $owner->getKey(),
        ])->save();
    }
}
