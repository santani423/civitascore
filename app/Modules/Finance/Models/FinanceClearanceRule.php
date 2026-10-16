<?php

namespace Modules\Finance\Models;

use App\Support\Scoping\ScopesToInstitution;
use App\Support\Tenancy\TenantScoped;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Academic\Models\StudyProgram;
use Modules\Finance\Enums\ClearanceRequirement;

/** Aturan cekal keuangan (kondisi FinanceBlockSource, §10.3). */
class FinanceClearanceRule extends Model implements ScopesToInstitution
{
    use HasUlids, TenantScoped;

    protected $fillable = ['university_id', 'code', 'name', 'services', 'requirement', 'bill_groups', 'terms', 'grace_days', 'min_outstanding_amount', 'include_penalties', 'study_program_id', 'active_from', 'active_until', 'is_active', 'created_by'];

    protected function casts(): array
    {
        return [
            'services' => 'array',
            'requirement' => ClearanceRequirement::class,
            'bill_groups' => 'array',
            'grace_days' => 'integer',
            'min_outstanding_amount' => 'decimal:2',
            'include_penalties' => 'boolean',
            'active_from' => 'date',
            'active_until' => 'date',
            'is_active' => 'boolean',
        ];
    }

    /** @return BelongsTo<StudyProgram, $this> */
    public function studyProgram(): BelongsTo
    {
        return $this->belongsTo(StudyProgram::class);
    }
}
