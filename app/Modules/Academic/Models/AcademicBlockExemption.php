<?php

namespace Modules\Academic\Models;

use App\Models\User;
use App\Support\Scoping\ScopesToInstitution;
use App\Support\Tenancy\TenantScoped;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Academic\Enums\BlockExemptionStatus;

/** Dispensasi cekal per layanan, berbatas waktu & beralasan (BR-SIA-CKL-04). */
class AcademicBlockExemption extends Model implements ScopesToInstitution
{
    use HasUlids, TenantScoped;

    protected $fillable = [
        'university_id', 'academic_block_id', 'service', 'valid_from', 'valid_until', 'reason', 'promise_amount', 'promise_date',
        'status', 'requested_by', 'granted_by', 'decided_at', 'decision_note',
    ];

    protected function casts(): array
    {
        return [
            'valid_from' => 'datetime',
            'valid_until' => 'datetime',
            'promise_amount' => 'decimal:2',
            'promise_date' => 'date',
            'status' => BlockExemptionStatus::class,
            'decided_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<AcademicBlock, $this> */
    public function block(): BelongsTo
    {
        return $this->belongsTo(AcademicBlock::class, 'academic_block_id');
    }

    /** @return BelongsTo<User, $this> */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /** @return BelongsTo<User, $this> */
    public function granter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_by');
    }
}
