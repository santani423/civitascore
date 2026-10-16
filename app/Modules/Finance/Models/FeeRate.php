<?php

namespace Modules\Finance\Models;

use App\Support\Scoping\ScopesToInstitution;
use App\Support\Tenancy\TenantScoped;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Academic\Models\StudyProgram;
use Modules\Finance\Enums\CalcBasis;

/** Baris tarif berdimensi (§8.3.1). */
class FeeRate extends Model implements ScopesToInstitution
{
    use HasUlids, TenantScoped;

    protected $fillable = ['university_id', 'fee_rate_table_id', 'fee_component_id', 'study_program_id', 'admission_year_from', 'admission_year_to', 'admission_path_code', 'study_system_code', 'ukt_group_id', 'semester_level_from', 'semester_level_to', 'calc_basis', 'amount', 'min_credits', 'max_credits', 'specificity_score'];

    protected function casts(): array
    {
        return [
            'calc_basis' => CalcBasis::class,
            'amount' => 'decimal:2',
            'admission_year_from' => 'integer',
            'admission_year_to' => 'integer',
            'semester_level_from' => 'integer',
            'semester_level_to' => 'integer',
            'min_credits' => 'integer',
            'max_credits' => 'integer',
            'specificity_score' => 'integer',
        ];
    }

    /** @return BelongsTo<FeeRateTable, $this> */
    public function rateTable(): BelongsTo
    {
        return $this->belongsTo(FeeRateTable::class, 'fee_rate_table_id');
    }

    /** @return BelongsTo<FeeComponent, $this> */
    public function component(): BelongsTo
    {
        return $this->belongsTo(FeeComponent::class, 'fee_component_id');
    }

    /** @return BelongsTo<StudyProgram, $this> */
    public function studyProgram(): BelongsTo
    {
        return $this->belongsTo(StudyProgram::class);
    }

    /** @return BelongsTo<UktGroup, $this> */
    public function uktGroup(): BelongsTo
    {
        return $this->belongsTo(UktGroup::class);
    }
}
