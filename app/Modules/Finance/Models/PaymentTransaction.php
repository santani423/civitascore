<?php

namespace Modules\Finance\Models;

use App\Models\User;
use App\Support\Scoping\ScopesToInstitution;
use App\Support\Tenancy\TenantScoped;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Academic\Models\Student;
use Modules\Finance\Enums\PaymentMethod;
use Modules\Finance\Enums\ReconciliationStatus;
use Modules\Finance\Enums\TransactionStatus;

/**
 * Uang masuk / upaya bayar (lapis status 2, rancangan KEU §8.2.5).
 * Target: `invoice`, `installment`, atau `deposit` (titipan). Status
 * `success` hanya lewat jalur sah BR-KEU-41 — tidak pernah dari klien.
 */
class PaymentTransaction extends Model implements ScopesToInstitution
{
    use HasUlids, TenantScoped;

    public const TARGET_INVOICE = 'invoice';

    public const TARGET_INSTALLMENT = 'installment';

    public const TARGET_DEPOSIT = 'deposit';

    protected $fillable = [
        'university_id', 'transaction_number', 'student_id', 'payer_name', 'payment_channel_id', 'provider', 'method', 'target_type',
        'target_id', 'amount', 'channel_fee', 'currency', 'status', 'idempotency_key', 'provider_reference', 'va_number', 'qr_string',
        'redirect_url', 'instructions', 'unique_code', 'expires_at', 'paid_at', 'cashier_session_id', 'receipt_number', 'recorded_by',
        'verified_by', 'verified_at', 'reconciliation_status', 'reconciled_at', 'refunded_amount', 'failure_reason', 'needs_resolution',
        'reversed_at', 'reversal_reason', 'recheck_attempts', 'next_recheck_at', 'lock_version',
    ];

    protected function casts(): array
    {
        return [
            'method' => PaymentMethod::class,
            'status' => TransactionStatus::class,
            'reconciliation_status' => ReconciliationStatus::class,
            'amount' => 'decimal:2',
            'channel_fee' => 'decimal:2',
            'refunded_amount' => 'decimal:2',
            'instructions' => 'array',
            'unique_code' => 'integer',
            'expires_at' => 'datetime',
            'paid_at' => 'datetime',
            'verified_at' => 'datetime',
            'reconciled_at' => 'datetime',
            'reversed_at' => 'datetime',
            'next_recheck_at' => 'datetime',
            'needs_resolution' => 'boolean',
        ];
    }

    /** @return BelongsTo<Student, $this> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /** @return BelongsTo<PaymentChannel, $this> */
    public function channel(): BelongsTo
    {
        return $this->belongsTo(PaymentChannel::class, 'payment_channel_id');
    }

    /** @return HasMany<Payment, $this> */
    public function allocations(): HasMany
    {
        return $this->hasMany(Payment::class, 'payment_transaction_id');
    }

    /** @return HasOne<PaymentProof, $this> */
    public function proof(): HasOne
    {
        return $this->hasOne(PaymentProof::class, 'payment_transaction_id');
    }

    /** @return BelongsTo<CashierSession, $this> */
    public function cashierSession(): BelongsTo
    {
        return $this->belongsTo(CashierSession::class);
    }

    /** @return BelongsTo<User, $this> */
    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    /** @return BelongsTo<User, $this> */
    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
