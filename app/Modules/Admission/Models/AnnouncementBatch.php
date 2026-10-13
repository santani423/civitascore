<?php

namespace Modules\Admission\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Admission\Enums\AnnouncementBatchStatus;

class AnnouncementBatch extends PmbModel
{
    protected $table = 'pmb_announcement_batches';

    protected function casts(): array
    {
        return [
            'status' => AnnouncementBatchStatus::class,
            'scheduled_publish_at' => 'datetime',
            'published_at' => 'datetime',
            'decisions_count' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<SelectionRun, $this>
     */
    public function run(): BelongsTo
    {
        return $this->belongsTo(SelectionRun::class, 'selection_run_id');
    }
}
