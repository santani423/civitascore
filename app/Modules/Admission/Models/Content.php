<?php

namespace Modules\Admission\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Admission\Enums\ContentStatus;
use Modules\Admission\Enums\ContentType;
use Modules\FileManagement\Models\FileUpload;

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
