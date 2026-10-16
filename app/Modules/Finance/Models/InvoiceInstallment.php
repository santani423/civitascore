<?php

namespace Modules\Finance\Models;

use App\Support\Scoping\ScopesToInstitution;
use App\Support\Tenancy\TenantScoped;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Finance\Enums\InstallmentStatus;

/** Termin cicilan (§8.2.4). */
class InvoiceInstallment extends Model implements ScopesToInstitution
{
    use HasUlids, TenantScoped;

    protected $fillable = ['university_id', 'invoice_id', 'installment_request_id', 'seq', 'schedule_version', 'amount', 'paid_amount', 'due_date', 'status', 'paid_at', 'superseded_at', 'lock_version'];

    protected function casts(): array
    {
        return [
            'status' => InstallmentStatus::class,
            'amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'due_date' => 'date',
            'paid_at' => 'datetime',
            'superseded_at' => 'datetime',
            'seq' => 'integer',
            'schedule_version' => 'integer',
        ];
    }

    /** @return BelongsTo<Invoice, $this> */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /** @return BelongsTo<InstallmentRequest, $this> */
    public function request(): BelongsTo
    {
        return $this->belongsTo(InstallmentRequest::class, 'installment_request_id');
    }
}
