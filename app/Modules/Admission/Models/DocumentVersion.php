<?php

namespace Modules\Admission\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\FileManagement\Models\FileUpload;

/**
 * @property string $id
 * @property string $university_id
 * @property string $application_document_id
 * @property int $version_no
 * @property string $file_upload_id
 * @property string $uploaded_by_type
 * @property string $uploaded_by_id
 * @property string $checksum
 * @property CarbonImmutable|null $created_at
 * @property-read ApplicationDocument $document
 * @property-read FileUpload $fileUpload
 * @property-read Collection<int, DocumentReview> $reviews
 */
class DocumentVersion extends PmbModel
{
    protected $table = 'pmb_document_versions';

    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'version_no' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<ApplicationDocument, $this>
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(ApplicationDocument::class, 'application_document_id');
    }

    /**
     * @return BelongsTo<FileUpload, $this>
     */
    public function fileUpload(): BelongsTo
    {
        return $this->belongsTo(FileUpload::class, 'file_upload_id');
    }

    /**
     * @return HasMany<DocumentReview, $this>
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(DocumentReview::class, 'document_version_id');
    }
}
