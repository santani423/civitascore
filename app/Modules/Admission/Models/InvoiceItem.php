<?php

namespace Modules\Admission\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $university_id
 * @property string $invoice_id
 * @property string $item_type
 * @property string $source_type
 * @property string|null $source_id
 * @property string $code
 * @property string $description
 * @property string $amount
 * @property int|null $installment_seq
 * @property CarbonImmutable|null $due_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read PmbInvoice $invoice
 */
class InvoiceItem extends PmbModel
{
    protected $table = 'pmb_invoice_items';

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'installment_seq' => 'integer',
            'due_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<PmbInvoice, $this>
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(PmbInvoice::class, 'invoice_id');
    }
}
