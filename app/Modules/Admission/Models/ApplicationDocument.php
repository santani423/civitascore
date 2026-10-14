<?php

namespace Modules\Admission\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Admission\Enums\DocumentStage;
use Modules\Admission\Enums\DocumentStatus;

/**
 * @property string $id
 * @property string $university_id
 * @property string $application_id
 * @property string $document_type_id
 * @property DocumentStage $stage
 * @property int $slot
 * @property bool $is_mandatory
 * @property DocumentStatus $status
 * @property string|null $current_version_id
 * @property CarbonImmutable|null $locked_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Application $application
 * @property-read DocumentType $documentType
 * @property-read Collection<int, DocumentVersion> $versions
 * @property-read DocumentVersion|null $currentVersion
 */
class ApplicationDocument extends PmbModel
{
    protected $table = 'pmb_application_documents';

    protected function casts(): array
    {
        return [
            'stage' => DocumentStage::class,
            'status' => DocumentStatus::class,
            'is_mandatory' => 'boolean',
            'slot' => 'integer',
            'locked_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Application, $this>
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class, 'application_id');
    }

    /**
     * @return BelongsTo<DocumentType, $this>
     */
    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class, 'document_type_id');
    }

    /**
     * @return HasMany<DocumentVersion, $this>
     */
    public function versions(): HasMany
    {
        return $this->hasMany(DocumentVersion::class, 'application_document_id')->orderBy('version_no');
    }

    /**
     * @return BelongsTo<DocumentVersion, $this>
     */
    public function currentVersion(): BelongsTo
    {
        return $this->belongsTo(DocumentVersion::class, 'current_version_id');
    }
}
