<?php

namespace Modules\Admission\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Admission\Enums\DocumentStage;

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
