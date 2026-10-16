<?php

namespace Modules\Finance\Models;

use App\Models\User;
use App\Support\Scoping\ScopesToInstitution;
use App\Support\Tenancy\TenantScoped;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Finance\Enums\PenaltyCalcType;
use Modules\Finance\Enums\PenaltyPeriodUnit;
use Modules\Finance\Enums\PenaltyRuleStatus;

/** Aturan denda keterlambatan (FR-DND-01). */
class PenaltyRule extends Model implements ScopesToInstitution
{
    use HasUlids, TenantScoped;

    protected $fillable = ['university_id', 'code', 'name', 'bill_groups', 'grace_days', 'calc_type', 'period_unit', 'rate_percent', 'flat_amount', 'cap_amount', 'cap_percent', 'max_periods', 'effective_from', 'effective_until', 'status', 'decree_reference', 'submitted_by', 'approved_by', 'approved_at', 'created_by'];

    protected function casts(): array
    {
        return [
            'bill_groups' => 'array',
            'grace_days' => 'integer',
            'calc_type' => PenaltyCalcType::class,
            'period_unit' => PenaltyPeriodUnit::class,
            'rate_percent' => 'decimal:4',
            'flat_amount' => 'decimal:2',
            'cap_amount' => 'decimal:2',
            'cap_percent' => 'decimal:2',
            'max_periods' => 'integer',
            'effective_from' => 'date',
            'effective_until' => 'date',
            'status' => PenaltyRuleStatus::class,
            'approved_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    /** @return BelongsTo<User, $this> */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
