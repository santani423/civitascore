<?php

namespace Modules\Admission\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Admission\Enums\DocumentStage;

/**
 * @property string $id
 * @property string $university_id
 * @property string $wave_path_id
 * @property string $document_type_id
 * @property DocumentStage $stage
 * @property bool $is_mandatory
 * @property array<mixed>|null $condition
 * @property string|null $instructions
 * @property int $sort_order
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read WavePath $wavePath
 * @property-read DocumentType $documentType
 */
class DocumentRequirement extends PmbModel
{
    protected $table = 'pmb_document_requirements';

    protected function casts(): array
    {
        return [
            'stage' => DocumentStage::class,
            'is_mandatory' => 'boolean',
            'condition' => 'array',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<WavePath, $this>
     */
    public function wavePath(): BelongsTo
    {
        return $this->belongsTo(WavePath::class, 'wave_path_id');
    }

    /**
     * @return BelongsTo<DocumentType, $this>
     */
    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class, 'document_type_id');
    }
}
