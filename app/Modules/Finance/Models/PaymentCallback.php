<?php

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Callback penyedia apa adanya (dedupe & audit, BR-KEU-44). Sengaja tidak
 * TenantScoped: diterima sebelum tenant diketahui; `university_id` diisi
 * setelah transaksi ditemukan.
 */
class PaymentCallback extends Model
{
    use HasUlids;

    protected $fillable = [
        'university_id', 'provider', 'provider_event_id', 'payment_transaction_id', 'signature_valid', 'payload',
        'received_at', 'processed_at', 'processing_result', 'error_message',
    ];

    protected function casts(): array
    {
        return [
            'signature_valid' => 'boolean',
            'payload' => 'array',
            'received_at' => 'datetime',
            'processed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<PaymentTransaction, $this> */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(PaymentTransaction::class, 'payment_transaction_id');
    }
}
