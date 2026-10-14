<?php

namespace Modules\Admission\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $id
 * @property string $university_id
 * @property string $code
 * @property string $name
 * @property string|null $description
 * @property array<mixed> $allowed_mime_types
 * @property int $max_size_kb
 * @property bool $allows_multiple
 * @property bool $requires_original_check
 * @property bool $is_sensitive
 * @property string $retention_class
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 */
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
