<?php

namespace Modules\Admission\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Admission\Enums\AnnouncementBatchStatus;

/**
 * @property string $id
 * @property string $university_id
 * @property string $selection_run_id
 * @property CarbonImmutable $scheduled_publish_at
 * @property AnnouncementBatchStatus $status
 * @property CarbonImmutable|null $published_at
 * @property int $decisions_count
 * @property string|null $failure_reason
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read SelectionRun $run
 */
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
