<?php

namespace Modules\Admission\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
