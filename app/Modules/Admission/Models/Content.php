<?php

namespace Modules\Admission\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Admission\Enums\ContentStatus;
use Modules\Admission\Enums\ContentType;
use Modules\FileManagement\Models\FileUpload;

/**
 * @property string $id
 * @property string $university_id
 * @property ContentType $type
 * @property string|null $period_id
 * @property string|null $slug
 * @property string $title
 * @property string|null $body
 * @property string|null $category
 * @property string|null $file_id
 * @property int $version
 * @property ContentStatus $status
 * @property CarbonImmutable|null $publish_at
 * @property CarbonImmutable|null $unpublish_at
 * @property string|null $seo_title
 * @property string|null $seo_description
 * @property int $sort_order
 * @property string|null $created_by
 * @property string|null $updated_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property-read Period|null $period
 * @property-read FileUpload|null $file
 */
class Content extends PmbModel
{
    use SoftDeletes;

    protected $table = 'pmb_contents';

    protected function casts(): array
    {
        return [
            'type' => ContentType::class,
            'status' => ContentStatus::class,
            'version' => 'integer',
            'publish_at' => 'datetime',
            'unpublish_at' => 'datetime',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Period, $this>
     */
    public function period(): BelongsTo
    {
        return $this->belongsTo(Period::class, 'period_id');
    }

    /**
     * @return BelongsTo<FileUpload, $this>
     */
    public function file(): BelongsTo
    {
        return $this->belongsTo(FileUpload::class, 'file_id');
    }
}
