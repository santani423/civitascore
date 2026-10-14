<?php

namespace Modules\Admission\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Admission\Enums\ReconciliationResolution;
use Modules\Admission\Enums\ReconciliationResult;

/**
 * @property string $id
 * @property string $university_id
 * @property string $batch_id
 * @property string|null $transaction_id
 * @property string|null $external_reference
 * @property string|null $external_amount
 * @property CarbonImmutable|null $external_date
 * @property string|null $external_description
 * @property ReconciliationResult $result
 * @property ReconciliationResolution|null $resolution
 * @property string|null $resolution_note
 * @property string|null $resolved_by
 * @property CarbonImmutable|null $resolved_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read ReconciliationBatch $batch
 * @property-read PaymentTransaction|null $transaction
 */
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
