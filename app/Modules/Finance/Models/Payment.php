<?php

namespace Modules\Finance\Models;

use App\Models\User;
use App\Support\Scoping\ScopesToInstitution;
use App\Support\Tenancy\TenantScoped;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Finance\Database\Factories\PaymentFactory;
use Modules\Tenancy\Models\University;

/**
 * Alokasi dana sukses ke satu tagihan/termin (rancangan KEU KD-KEU-03).
 * Uang masuknya dicatat di PaymentTransaction; baris lama hasil posting PMB
 * tetap sah tanpa transaksi. `status` posted/reversed.
 *
 * @property string $id
 * @property string $university_id
 * @property string $invoice_id
 * @property string|null $payment_transaction_id
 * @property string|null $invoice_installment_id
 * @property string $amount
 * @property string $allocation_type
 * @property string $status
 * @property CarbonImmutable $paid_at
 * @property string $method
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Invoice $invoice
 */
class Payment extends Model implements ScopesToInstitution
{
    /** @use HasFactory<PaymentFactory> */
    use HasFactory, HasUlids, TenantScoped;

    public const POSTED = 'posted';

    public const REVERSED = 'reversed';

    protected $fillable = [
        'university_id', 'invoice_id', 'payment_transaction_id', 'invoice_installment_id', 'amount', 'allocation_type', 'status',
        'paid_at', 'effective_at', 'method', 'reference', 'channel', 'reversed_at', 'reversed_by', 'reversal_reason', 'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'date',
            'effective_at' => 'datetime',
            'reversed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<University, $this> */
    public function university(): BelongsTo
    {
        return $this->belongsTo(University::class);
    }

    /** @return BelongsTo<Invoice, $this> */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /** @return BelongsTo<PaymentTransaction, $this> */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(PaymentTransaction::class, 'payment_transaction_id');
    }

    /** @return BelongsTo<InvoiceInstallment, $this> */
    public function installment(): BelongsTo
    {
        return $this->belongsTo(InvoiceInstallment::class, 'invoice_installment_id');
    }

    /** @return BelongsTo<User, $this> */
    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    protected static function newFactory(): PaymentFactory
    {
        return PaymentFactory::new();
    }
}
