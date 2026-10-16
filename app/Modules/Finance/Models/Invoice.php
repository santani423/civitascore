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
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Academic\Models\AcademicTerm;
use Modules\Academic\Models\Student;
use Modules\Finance\Database\Factories\InvoiceFactory;
use Modules\Finance\Enums\BillGroup;
use Modules\Finance\Enums\InvoiceStatus;
use Modules\Finance\Support\Money;
use Modules\Tenancy\Models\University;

/**
 * Tagihan mahasiswa. Arti kolom lama dipertahankan (rancangan KEU §9.2):
 * `amount` = neto kewajiban saat ini, `paid_amount` = Σ alokasi posted.
 * Status selalu turunan — hanya diubah InvoiceLedger (BR-KEU-09).
 *
 * @property string $id
 * @property string $university_id
 * @property string $student_id
 * @property string|null $academic_term_id
 * @property string|null $invoice_number
 * @property string $period
 * @property BillGroup $bill_group
 * @property string|null $parent_invoice_id
 * @property string $gross_amount
 * @property string $discount_amount
 * @property string $adjustment_amount
 * @property string $amount
 * @property string $paid_amount
 * @property InvoiceStatus $status
 * @property CarbonImmutable $due_date
 * @property CarbonImmutable|null $issued_at
 * @property CarbonImmutable|null $settled_at
 * @property bool $is_overdue
 * @property string|null $source_type
 * @property string|null $source_id
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Student $student
 */
class Invoice extends Model implements ScopesToInstitution
{
    /** @use HasFactory<InvoiceFactory> */
    use HasFactory, HasUlids, TenantScoped;

    protected $fillable = [
        'university_id', 'student_id', 'academic_term_id', 'invoice_number', 'period', 'bill_group', 'parent_invoice_id',
        'gross_amount', 'discount_amount', 'adjustment_amount', 'amount', 'paid_amount', 'status', 'due_date',
        'source_type', 'source_id', 'issued_at', 'settled_at', 'cancelled_at', 'cancel_reason', 'is_overdue', 'overdue_since',
        'rate_snapshot', 'billing_run_id', 'needs_resolution', 'notes', 'lock_version', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => InvoiceStatus::class,
            'bill_group' => BillGroup::class,
            'gross_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'adjustment_amount' => 'decimal:2',
            'amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'due_date' => 'date',
            'issued_at' => 'datetime',
            'settled_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'is_overdue' => 'boolean',
            'overdue_since' => 'date',
            'rate_snapshot' => 'array',
            'needs_resolution' => 'boolean',
        ];
    }

    /** Sisa kewajiban (0 untuk tagihan yang tidak lagi menjadi piutang). */
    public function outstanding(): string
    {
        if (! $this->status->isOpen()) {
            return '0.00';
        }

        return Money::fromCents(max(0, Money::toCents($this->amount) - Money::toCents($this->paid_amount)));
    }

    /** @return BelongsTo<University, $this> */
    public function university(): BelongsTo
    {
        return $this->belongsTo(University::class);
    }

    /** @return BelongsTo<Student, $this> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /** @return BelongsTo<AcademicTerm, $this> */
    public function academicTerm(): BelongsTo
    {
        return $this->belongsTo(AcademicTerm::class);
    }

    /** @return BelongsTo<Invoice, $this> */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'parent_invoice_id');
    }

    /** @return HasMany<Invoice, $this> */
    public function penaltyInvoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'parent_invoice_id');
    }

    /** Alokasi pembayaran (termasuk yang sudah dibalik). @return HasMany<Payment, $this> */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /** @return HasMany<InvoiceItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class)->orderBy('sort_order');
    }

    /** Termin jadwal aktif (jadwal lama berstatus superseded dikecualikan). @return HasMany<InvoiceInstallment, $this> */
    public function installments(): HasMany
    {
        return $this->hasMany(InvoiceInstallment::class)->whereNull('superseded_at')->orderBy('seq');
    }

    /** @return HasMany<InvoiceAdjustment, $this> */
    public function adjustments(): HasMany
    {
        return $this->hasMany(InvoiceAdjustment::class);
    }

    /** @return HasMany<PenaltyAccrual, $this> */
    public function penaltyAccruals(): HasMany
    {
        return $this->hasMany(PenaltyAccrual::class);
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    protected static function newFactory(): InvoiceFactory
    {
        return InvoiceFactory::new();
    }
}
