<?php

namespace Modules\Admission\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class DocumentType extends PmbModel
{
    use SoftDeletes;

    protected $table = 'pmb_document_types';

    protected function casts(): array
    {
        return [
            'allowed_mime_types' => 'array',
            'max_size_kb' => 'integer',
            'allows_multiple' => 'boolean',
            'requires_original_check' => 'boolean',
            'is_sensitive' => 'boolean',
        ];
    }
}
