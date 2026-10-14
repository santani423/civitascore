<?php

namespace Modules\Admission\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Webhook gateway apa adanya (05 §F.4). Sengaja TIDAK tenant-scoped:
 * webhook diterima sebelum tenant diketahui; `university_id` diisi setelah
 * transaksi ditemukan. Insert-only kecuali kolom hasil pemrosesan.
 *
 *
 * @property string $id
 * @property string|null $university_id
 * @property string $provider
 * @property string $provider_event_id
 * @property string|null $transaction_id
 * @property bool $signature_valid
 * @property array<mixed>|null $headers
 * @property array<mixed> $payload
 * @property CarbonImmutable $received_at
 * @property CarbonImmutable|null $processed_at
 * @property string|null $processing_result
 * @property string|null $error_message
 * @property int $attempts
 * @property CarbonImmutable|null $next_attempt_at
 * @property-read PaymentTransaction|null $transaction
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
