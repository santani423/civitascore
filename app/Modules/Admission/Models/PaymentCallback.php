<?php

namespace Modules\Admission\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Webhook gateway apa adanya (05 §F.4). Sengaja TIDAK tenant-scoped:
 * webhook diterima sebelum tenant diketahui; `university_id` diisi setelah
 * transaksi ditemukan. Insert-only kecuali kolom hasil pemrosesan.
 */
class PaymentCallback extends Model
{
    use HasUlids;

    public $timestamps = false;

    protected $table = 'pmb_payment_callbacks';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'signature_valid' => 'boolean',
            'headers' => 'array',
            'payload' => 'array',
            'received_at' => 'datetime',
            'processed_at' => 'datetime',
            'next_attempt_at' => 'datetime',
            'attempts' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<PaymentTransaction, $this>
     */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(PaymentTransaction::class, 'transaction_id')->withoutGlobalScopes();
    }
}
