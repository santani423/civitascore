<?php

namespace Modules\Admission\Models;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Admission\Enums\TransactionStatus;

/**
 * @property string $id
 * @property string $university_id
 * @property string $invoice_id
 * @property string $channel
 * @property string $provider
 * @property string $amount
 * @property string $currency
 * @property TransactionStatus $status
 * @property string $idempotency_key
 * @property string|null $provider_reference
 * @property string|null $va_number
 * @property string|null $qr_string
 * @property string|null $redirect_url
 * @property array<mixed>|null $instructions
 * @property int|null $unique_code
 * @property CarbonImmutable|null $expires_at
 * @property CarbonImmutable|null $paid_at
 * @property string|null $provider_status_raw
 * @property string|null $receipt_number
 * @property string|null $recorded_by
 * @property string|null $failure_reason
 * @property int $recheck_attempts
 * @property CarbonImmutable|null $next_recheck_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read PmbInvoice $invoice
 * @property-read ManualPaymentProof|null $manualProof
 * @property-read Collection<int, PaymentCallback> $callbacks
 * @property-read User|null $recorder
 */
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
