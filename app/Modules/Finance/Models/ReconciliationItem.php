<?php

namespace Modules\Finance\Models;

use App\Models\User;
use App\Support\Scoping\ScopesToInstitution;
use App\Support\Tenancy\TenantScoped;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Finance\Enums\ReconciliationResult;

/** Baris pencocokan rekonsiliasi (FR-REK-02). */
class ReconciliationItem extends Model implements ScopesToInstitution
{
    use HasUlids, TenantScoped;

    protected $fillable = ['university_id', 'batch_id', 'payment_transaction_id', 'external_reference', 'external_amount', 'external_date', 'external_description', 'result', 'resolution', 'resolution_note', 'resolved_by', 'resolved_at'];

    protected function casts(): array
    {
        return [
            'external_amount' => 'decimal:2',
            'external_date' => 'date',
            'result' => ReconciliationResult::class,
            'resolved_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<ReconciliationBatch, $this> */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(ReconciliationBatch::class, 'batch_id');
    }

    /** @return BelongsTo<PaymentTransaction, $this> */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(PaymentTransaction::class, 'payment_transaction_id');
    }

    /** @return BelongsTo<User, $this> */
    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}
