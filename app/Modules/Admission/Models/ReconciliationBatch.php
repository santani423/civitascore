<?php

namespace Modules\Admission\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property string $university_id
 * @property string $provider
 * @property CarbonImmutable $statement_date
 * @property string|null $source_file_id
 * @property string $status
 * @property int $total_items
 * @property int $matched_items
 * @property string|null $created_by
 * @property string|null $closed_by
 * @property CarbonImmutable|null $closed_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Collection<int, ReconciliationItem> $items
 */
class ReconciliationBatch extends PmbModel
{
    protected $table = 'pmb_reconciliation_batches';

    protected function casts(): array
    {
        return [
            'statement_date' => 'date',
            'total_items' => 'integer',
            'matched_items' => 'integer',
            'closed_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<ReconciliationItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(ReconciliationItem::class, 'batch_id');
    }
}
