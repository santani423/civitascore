<?php

namespace Modules\HumanResource\Services\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Modules\HumanResource\Support\HrFileAttacher;

/**
 * Pola simpan bersama untuk record SDM yang punya kolom lampiran
 * (document_file_id, decree_file_id, ...): validasi berkas dulu, simpan
 * record, lalu tautkan berkas ke record (fileable) supaya aksesnya
 * dilindungi RestrictsFileAccess.
 */
trait SavesRecordWithFiles
{
    /**
     * @template TModel of Model
     *
     * @param  TModel  $record
     * @param  array<string, mixed>  $data
     * @param  array<int, string>  $fileFields
     * @return TModel
     */
    protected function saveWithFiles(Model $record, array $data, array $fileFields, User $actor): Model
    {
        $attacher = app(HrFileAttacher::class);
        $files = [];

        foreach ($fileFields as $field) {
            if (array_key_exists($field, $data)) {
                $files[$field] = $attacher->resolve($data[$field], $field, $actor, $record->exists ? $record : null);
            }
        }

        $record->fill($data);
        $record->save();

        foreach ($files as $file) {
            $attacher->attach($file, $record);
        }

        return $record;
    }
}
