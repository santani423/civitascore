<?php

namespace Modules\Finance\Models;

use App\Support\Scoping\ScopesToInstitution;
use App\Support\Tenancy\TenantScoped;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Academic\Models\Student;
use Modules\Finance\Enums\RedemptionStatus;

/** Penukaran voucher (FR-VCR-03). */
class VoucherRedemption extends Model implements ScopesToInstitution
{
    use HasUlids, TenantScoped;

    protected $fillable = ['university_id', 'voucher_id', 'student_id', 'invoice_id', 'discount_amount', 'status', 'redeemed_by', 'reversed_at', 'reversed_by'];

    protected function casts(): array
    {
        return [
            'discount_amount' => 'decimal:2',
            'status' => RedemptionStatus::class,
            'reversed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Voucher, $this> */
    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class);
    }

    /** @return BelongsTo<Student, $this> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /** @return BelongsTo<Invoice, $this> */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
