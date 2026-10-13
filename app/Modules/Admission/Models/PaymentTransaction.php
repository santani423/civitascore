<?php

namespace Modules\Admission\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Admission\Enums\TransactionStatus;

class PaymentTransaction extends PmbModel
{
    protected $table = 'pmb_payment_transactions';

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'status' => TransactionStatus::class,
            'instructions' => 'array',
            'expires_at' => 'datetime',
            'paid_at' => 'datetime',
            'next_recheck_at' => 'datetime',
            'unique_code' => 'integer',
            'recheck_attempts' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<PmbInvoice, $this>
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(PmbInvoice::class, 'invoice_id');
    }

    /**
     * @return HasOne<ManualPaymentProof, $this>
     */
    public function manualProof(): HasOne
    {
        return $this->hasOne(ManualPaymentProof::class, 'transaction_id');
    }

    /**
     * @return HasMany<PaymentCallback, $this>
     */
    public function callbacks(): HasMany
    {
        return $this->hasMany(PaymentCallback::class, 'transaction_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
