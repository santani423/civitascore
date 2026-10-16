<?php

namespace Modules\Finance\Models;

use App\Support\Scoping\ScopesToInstitution;
use App\Support\Tenancy\TenantScoped;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Academic\Models\Student;

/** Hasil billing run per mahasiswa. */
class BillingRunItem extends Model implements ScopesToInstitution
{
    use HasUlids, TenantScoped;

    protected $fillable = ['university_id', 'billing_run_id', 'student_id', 'status', 'skip_reason', 'gross_amount', 'discount_amount', 'net_amount', 'invoice_id', 'lines', 'error_message'];

    protected function casts(): array
    {
        return [
            'gross_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'net_amount' => 'decimal:2',
            'lines' => 'array',
        ];
    }

    /** @return BelongsTo<BillingRun, $this> */
    public function run(): BelongsTo
    {
        return $this->belongsTo(BillingRun::class, 'billing_run_id');
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
