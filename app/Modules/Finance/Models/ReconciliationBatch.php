<?php

namespace Modules\Finance\Models;

use App\Models\User;
use App\Support\Scoping\ScopesToInstitution;
use App\Support\Tenancy\TenantScoped;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Finance\Enums\ReconciliationBatchStatus;

/** Batch rekonsiliasi (FR-REK-01). */
class ReconciliationBatch extends Model implements ScopesToInstitution
{
    use HasUlids, TenantScoped;

    protected $fillable = ['university_id', 'batch_number', 'source', 'payment_channel_id', 'statement_date', 'source_file_id', 'status', 'total_items', 'matched_items', 'exception_items', 'total_amount', 'created_by', 'closed_by', 'closed_at'];

    protected function casts(): array
    {
        return [
            'statement_date' => 'date',
            'status' => ReconciliationBatchStatus::class,
            'total_amount' => 'decimal:2',
            'closed_at' => 'datetime',
            'total_items' => 'integer',
            'matched_items' => 'integer',
            'exception_items' => 'integer',
        ];
    }

    /** @return HasMany<ReconciliationItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(ReconciliationItem::class, 'batch_id');
    }

    /** @return BelongsTo<PaymentChannel, $this> */
    public function channel(): BelongsTo
    {
        return $this->belongsTo(PaymentChannel::class, 'payment_channel_id');
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return BelongsTo<User, $this> */
    public function closer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }
}
