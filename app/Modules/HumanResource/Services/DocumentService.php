<?php

namespace Modules\HumanResource\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\Academic\Models\Employee;
use Modules\HumanResource\Enums\DocumentStatus;
use Modules\HumanResource\Models\EmployeeDocument;
use Modules\HumanResource\Services\Concerns\SavesRecordWithFiles;

class DocumentService
{
    use SavesRecordWithFiles;

    /**
     * Unggah dokumen baru. Kalau `replaces_document_id` diisi, dokumen ini
     * menjadi versi berikutnya: versi lama tetap tersimpan (is_current =
     * false), tidak dihapus.
     *
     * @param  array<string, mixed>  $data
     */
    public function upload(Employee $employee, array $data, User $actor, ?EmployeeDocument $replaces = null): EmployeeDocument
    {
        return DB::transaction(function () use ($employee, $data, $actor, $replaces): EmployeeDocument {
            $document = new EmployeeDocument([
                'employee_id' => $employee->id,
                'university_id' => $employee->university_id,
                'status' => DocumentStatus::Pending,
                'version' => $replaces !== null ? $replaces->version + 1 : 1,
                'previous_version_id' => $replaces?->id,
                'is_current' => true,
            ]);

            if ($replaces !== null) {
                $data['document_type'] ??= $replaces->document_type;
                $replaces->update(['is_current' => false]);
            }

            return $this->saveWithFiles($document, $data, ['file_upload_id'], $actor);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateMetadata(EmployeeDocument $document, array $data): EmployeeDocument
    {
        $document->update($data);

        return $document;
    }

    public function verify(EmployeeDocument $document, DocumentStatus $status, ?string $note, User $actor): EmployeeDocument
    {
        $document->update([
            'status' => $status,
            'verification_note' => $note,
            'verified_by' => $actor->id,
            'verified_at' => now(),
        ]);

        return $document;
    }

    /**
     * Soft delete. Kalau yang dihapus adalah versi berlaku, versi
     * sebelumnya dijadikan berlaku kembali.
     */
    public function delete(EmployeeDocument $document): void
    {
        DB::transaction(function () use ($document): void {
            if ($document->is_current && $document->previous_version_id !== null) {
                EmployeeDocument::query()->whereKey($document->previous_version_id)->update(['is_current' => true]);
            }

            $document->delete();
        });
    }
}
