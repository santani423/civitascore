<?php

namespace Modules\Admission\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Admission\Enums\ReconciliationResolution;
use Modules\Admission\Enums\ReconciliationResult;

class ReconciliationItem extends PmbModel
{
    protected $table = 'pmb_reconciliation_items';

    protected function casts(): array
    {
        return [
            'result' => ReconciliationResult::class,
            'resolution' => ReconciliationResolution::class,
            'external_amount' => 'decimal:2',
            'external_date' => 'date',
            'resolved_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<ReconciliationBatch, $this>
     */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(ReconciliationBatch::class, 'batch_id');
    }

    /**
     * @return BelongsTo<PaymentTransaction, $this>
     */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(PaymentTransaction::class, 'transaction_id');
    }
}
