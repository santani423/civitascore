<?php

namespace Modules\FileManagement\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use Modules\FileManagement\Enums\FileUploadStatus;
use Modules\FileManagement\Models\FileUpload;

/**
 * Menautkan berkas yang sudah diunggah lewat POST /file-uploads ke record
 * pemiliknya (mengisi fileable_type/id) — versi umum dari pola yang sama di
 * Modules\HumanResource\Support\HrFileAttacher, untuk modul selain SDM.
 * Setelah tertaut ke model yang mengimplementasikan RestrictsFileAccess,
 * FileUploadPolicy menyerahkan keputusan akses ke record tersebut.
 *
 * Validasi: berkas harus milik tenant ini (query ter-scope TenantScoped →
 * id tenant lain = "tidak ada"), sudah lolos pemindaian, belum tertaut ke
 * record lain, dan diunggah oleh user yang sama — atau memang sudah
 * tertaut ke record yang sama. Mencegah melampirkan berkas milik orang lain
 * hanya dengan menebak id-nya.
 */
class FileAttacher
{
    /**
     * @param  array<int, string>|null  $allowedExtensions  null = tidak dibatasi di sini (cukup aturan POST /file-uploads)
     */
    public function resolve(
        ?string $fileId,
        string $field,
        User $actor,
        ?Model $currentOwner = null,
        ?array $allowedExtensions = null,
        ?int $maxSizeMb = null,
    ): ?FileUpload {
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

        if ($allowedExtensions !== null && ! in_array(strtolower($file->extension), $allowedExtensions, true)) {
            throw ValidationException::withMessages([
                $field => 'Format berkas tidak diizinkan. Format yang diterima: '.implode(', ', $allowedExtensions).'.',
            ]);
        }

        if ($maxSizeMb !== null && $file->size_bytes > $maxSizeMb * 1024 * 1024) {
            throw ValidationException::withMessages([$field => "Ukuran berkas melebihi batas {$maxSizeMb} MB."]);
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
