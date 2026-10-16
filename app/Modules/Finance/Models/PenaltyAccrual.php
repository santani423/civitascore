<?php

namespace Modules\Finance\Models;

use App\Support\Scoping\ScopesToInstitution;
use App\Support\Tenancy\TenantScoped;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Finance\Enums\PenaltyAccrualStatus;

/** Denda berjalan per tagihan/termin (FR-DND-02). */
class PenaltyAccrual extends Model implements ScopesToInstitution
{
    use HasUlids, TenantScoped;

    protected $fillable = ['university_id', 'invoice_id', 'invoice_installment_id', 'penalty_rule_id', 'active_key', 'overdue_from', 'last_calculated_on', 'periods_counted', 'accrued_amount', 'status', 'penalty_invoice_id'];

    protected function casts(): array
    {
        return [
            'overdue_from' => 'date',
            'last_calculated_on' => 'date',
            'periods_counted' => 'integer',
            'accrued_amount' => 'decimal:2',
            'status' => PenaltyAccrualStatus::class,
        ];
    }

    /** @return BelongsTo<Invoice, $this> */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /** @return BelongsTo<InvoiceInstallment, $this> */
    public function installment(): BelongsTo
    {
        return $this->belongsTo(InvoiceInstallment::class, 'invoice_installment_id');
    }

    /** @return BelongsTo<PenaltyRule, $this> */
    public function rule(): BelongsTo
    {
        return $this->belongsTo(PenaltyRule::class, 'penalty_rule_id');
    }

    /** @return BelongsTo<Invoice, $this> */
    public function penaltyInvoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'penalty_invoice_id');
    }
}
