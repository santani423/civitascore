<?php

namespace Modules\Finance\Models;

use App\Models\User;
use App\Support\Scoping\ScopesToInstitution;
use App\Support\Tenancy\TenantScoped;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Academic\Models\Student;
use Modules\Finance\Enums\InstallmentRequestStatus;

/** Pengajuan/rencana cicilan suatu tagihan (FR-CCL-02). */
class InstallmentRequest extends Model implements ScopesToInstitution
{
    use HasUlids, TenantScoped;

    protected $fillable = ['university_id', 'student_id', 'invoice_id', 'installment_plan_id', 'terms', 'kind', 'status', 'plan_status', 'schedule_version', 'reason', 'requested_by', 'decided_by', 'decided_at', 'decision_note'];

    protected function casts(): array
    {
        return [
            'status' => InstallmentRequestStatus::class,
            'terms' => 'array',
            'schedule_version' => 'integer',
            'decided_at' => 'datetime',
        ];
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

    /** @return BelongsTo<InstallmentPlan, $this> */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(InstallmentPlan::class, 'installment_plan_id');
    }

    /** @return BelongsTo<User, $this> */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /** @return BelongsTo<User, $this> */
    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    /** @return HasMany<InvoiceInstallment, $this> */
    public function installments(): HasMany
    {
        return $this->hasMany(InvoiceInstallment::class);
    }
}
