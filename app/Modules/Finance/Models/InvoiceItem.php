<?php

namespace Modules\Finance\Models;

use App\Support\Scoping\ScopesToInstitution;
use App\Support\Tenancy\TenantScoped;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Finance\Enums\InvoiceLineType;

/** Rincian tagihan; potongan bernilai negatif (§8.3.3). */
class InvoiceItem extends Model implements ScopesToInstitution
{
    use HasUlids, TenantScoped;

    protected $fillable = ['university_id', 'invoice_id', 'line_type', 'fee_component_id', 'description', 'quantity', 'unit_amount', 'amount', 'source_type', 'source_id', 'active_key', 'sort_order'];

    protected function casts(): array
    {
        return [
            'line_type' => InvoiceLineType::class,
            'quantity' => 'decimal:2',
            'unit_amount' => 'decimal:2',
            'amount' => 'decimal:2',
            'sort_order' => 'integer',
        ];
    }

    /** @return BelongsTo<Invoice, $this> */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /** @return BelongsTo<FeeComponent, $this> */
    public function component(): BelongsTo
    {
        return $this->belongsTo(FeeComponent::class, 'fee_component_id');
    }
}
