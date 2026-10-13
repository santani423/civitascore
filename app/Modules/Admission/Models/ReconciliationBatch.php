<?php

namespace Modules\Admission\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

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
