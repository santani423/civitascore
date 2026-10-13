<?php

namespace Modules\Admission\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Academic\Models\AcademicTerm;
use Modules\Admission\Enums\PeriodStatus;

class Period extends PmbModel
{
    protected $table = 'pmb_periods';

    protected function casts(): array
    {
        return [
            'status' => PeriodStatus::class,
            'starts_on' => 'date',
            'ends_on' => 'date',
            'published_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<AcademicTerm, $this>
     */
    public function entryTerm(): BelongsTo
    {
        return $this->belongsTo(AcademicTerm::class, 'entry_academic_term_id');
    }

    /**
     * @return HasMany<Wave, $this>
     */
    public function waves(): HasMany
    {
        return $this->hasMany(Wave::class, 'period_id');
    }

    /**
     * @return HasMany<Quota, $this>
     */
    public function quotas(): HasMany
    {
        return $this->hasMany(Quota::class, 'period_id');
    }

    /**
     * @return HasMany<FeeComponent, $this>
     */
    public function feeComponents(): HasMany
    {
        return $this->hasMany(FeeComponent::class, 'period_id');
    }
}
