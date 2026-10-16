<?php

namespace Modules\Finance\Models;

use App\Models\User;
use App\Support\Scoping\ScopesToInstitution;
use App\Support\Tenancy\TenantScoped;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Academic\Models\AcademicTerm;
use Modules\Finance\Enums\BillGroup;
use Modules\Finance\Enums\BillingRunStatus;

/** Penerbitan tagihan massal: simulasi → persetujuan → posting (FR-TAG-01). */
class BillingRun extends Model implements ScopesToInstitution
{
    use HasUlids, TenantScoped;

    protected $fillable = ['university_id', 'run_number', 'academic_term_id', 'bill_group', 'fee_component_ids', 'scope', 'due_date', 'status', 'totals', 'posted_totals', 'created_by', 'submitted_at', 'approved_by', 'approved_at', 'rejection_reason', 'posted_at'];

    protected function casts(): array
    {
        return [
            'status' => BillingRunStatus::class,
            'bill_group' => BillGroup::class,
            'fee_component_ids' => 'array',
            'scope' => 'array',
            'totals' => 'array',
            'posted_totals' => 'array',
            'due_date' => 'date',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'posted_at' => 'datetime',
        ];
    }

    /** @return HasMany<BillingRunItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(BillingRunItem::class);
    }

    /** @return BelongsTo<AcademicTerm, $this> */
    public function academicTerm(): BelongsTo
    {
        return $this->belongsTo(AcademicTerm::class);
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return BelongsTo<User, $this> */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
