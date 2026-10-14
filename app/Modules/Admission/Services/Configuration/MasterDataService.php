<?php

namespace Modules\Admission\Services\Configuration;

use App\Support\Http\Exceptions\ConflictException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Modules\Admission\Models\AdmissionPath;
use Modules\Admission\Models\ApplicationDocument;
use Modules\Admission\Models\DocumentRequirement;
use Modules\Admission\Models\DocumentType;
use Modules\Admission\Models\FeeComponent;
use Modules\Admission\Models\Quota;
use Modules\Admission\Models\StudySystem;
use Modules\Admission\Models\WavePath;
use Modules\Admission\Support\HistoryRecorder;
use Modules\AuditLog\Enums\AuditAction;

/**
 * Master lintas periode: jalur (FR-CFG-03), sistem kuliah (FR-CFG-06),
 * katalog jenis dokumen (FR-CFG-08). Master yang sudah dipakai tidak dapat
 * dihapus, hanya dinonaktifkan.
 */
class MasterDataService
{
    private const PATH_FIELDS = ['code', 'name', 'category', 'description', 'eligible_degree_levels', 'requires_exam', 'requires_previous_institution', 'is_active'];

    private const SYSTEM_FIELDS = ['code', 'name', 'schedule_note', 'is_active'];

    private const DOCUMENT_FIELDS = ['code', 'name', 'description', 'allowed_mime_types', 'max_size_kb', 'allows_multiple', 'requires_original_check', 'is_sensitive', 'retention_class'];

    public function __construct(private readonly HistoryRecorder $history) {}

    /** @param  array<string, mixed>  $data */
    public function savePath(?AdmissionPath $path, array $data, string $userId): AdmissionPath
    {
        $values = Arr::only($data, self::PATH_FIELDS);

        if (isset($values['code'])) {
            $values['code'] = strtoupper($values['code']);
        }

        if (array_key_exists('eligible_degree_levels', $values) || $path === null) {
            $values['eligible_degree_levels'] = array_values((array) ($values['eligible_degree_levels'] ?? []));
        }

        return $this->save($path ?? new AdmissionPath, [...$values, 'updated_by' => $userId] + ($path ? [] : ['created_by' => $userId]));
    }

    public function deletePath(AdmissionPath $path): void
    {
        if (WavePath::query()->where('admission_path_id', $path->getKey())->exists() || Quota::query()->where('admission_path_id', $path->getKey())->exists()) {
            throw new ConflictException('Jalur sudah dipakai gelombang/kuota; nonaktifkan saja.', 'CONFIG_IN_USE');
        }

        $this->remove($path);
    }

    /** @param  array<string, mixed>  $data */
    public function saveStudySystem(?StudySystem $system, array $data): StudySystem
    {
        $values = Arr::only($data, self::SYSTEM_FIELDS);

        if (isset($values['code'])) {
            $values['code'] = strtoupper($values['code']);
        }

        return $this->save($system ?? new StudySystem, $values);
    }

    public function deleteStudySystem(StudySystem $system): void
    {
        if (Quota::query()->where('study_system_id', $system->getKey())->exists() || FeeComponent::query()->where('study_system_id', $system->getKey())->exists()) {
            throw new ConflictException('Sistem kuliah sudah dipakai kuota/biaya; nonaktifkan saja.', 'CONFIG_IN_USE');
        }

        $this->remove($system);
    }

    /** @param  array<string, mixed>  $data */
    public function saveDocumentType(?DocumentType $type, array $data): DocumentType
    {
        $values = Arr::only($data, self::DOCUMENT_FIELDS);

        if (isset($values['code'])) {
            $values['code'] = strtoupper($values['code']);
        }

        if ($type !== null && isset($values['code']) && $values['code'] !== $type->code && ApplicationDocument::query()->where('document_type_id', $type->getKey())->exists()) {
            throw new ConflictException('Kode jenis dokumen tidak dapat diubah setelah dipakai pendaftar.', 'CONFIG_IN_USE');
        }

        return $this->save($type ?? new DocumentType, $values);
    }

    public function deleteDocumentType(DocumentType $type): void
    {
        if (DocumentRequirement::query()->where('document_type_id', $type->getKey())->exists() || ApplicationDocument::query()->where('document_type_id', $type->getKey())->exists()) {
            throw new ConflictException('Jenis dokumen sudah dipakai persyaratan/pendaftar.', 'CONFIG_IN_USE');
        }

        $this->remove($type);
    }

    /**
     * @template TModel of Model
     *
     * @param  TModel  $model
     * @param  array<string, mixed>  $values
     * @return TModel
     */
    private function save(Model $model, array $values): Model
    {
        return DB::transaction(function () use ($model, $values): Model {
            $isNew = ! $model->exists;
            $model->forceFill($values);
            $old = $isNew ? [] : array_intersect_key($model->getOriginal(), $model->getDirty());
            $new = $model->getDirty();
            $model->save();
            $this->history->change($model, $old, $new, null, $isNew ? AuditAction::Created : AuditAction::Updated);

            return $model->refresh();
        });
    }

    private function remove(Model $model): void
    {
        DB::transaction(function () use ($model): void {
            $this->history->change($model, $model->getAttributes(), [], null, AuditAction::Deleted);
            $model->delete();
        });
    }
}
